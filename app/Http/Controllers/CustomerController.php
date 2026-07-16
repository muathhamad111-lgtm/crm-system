<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\CustomerActivationTask;
use App\Models\CustomerActivity;
use App\Models\CustomerAttachment;
use App\Models\CustomerContact;
use App\Models\CustomerSubscription;
use App\Models\Profile;
use App\Models\Request as CrmRequest;
use App\Models\RequestRating;
use App\Models\SuggestionVote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /** Statuses that count a request as "closed" (not open). */
    private const CLOSED_STATUSES = ['completed', 'closed', 'rejected', 'cancelled'];

    /** Statuses that count toward the "resolved" (closed/completed) tally. */
    private const RESOLVED_STATUSES = ['completed', 'closed'];

    /** Staff directory of customer profiles with request + CSAT rollups. */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $field = (string) $request->query('field', 'all');
        $sort = (string) $request->query('sort', 'requests');
        $now = now();

        // Base filter (search + business field) — reused for both list and KPI totals.
        $base = Profile::query()
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('full_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('region', 'like', $like);
                });
            })
            ->when($field !== '' && $field !== 'all', fn ($q) => $q->where('business_field', $field));

        $query = (clone $base)
            ->select('profiles.*')
            ->selectSub(
                CrmRequest::query()->selectRaw('count(*)')
                    ->whereColumn('requests.customer_id', 'profiles.id'),
                'requests_count'
            )
            ->selectSub(
                CrmRequest::query()->selectRaw('count(*)')
                    ->whereColumn('requests.customer_id', 'profiles.id')
                    ->whereNotIn('status', self::CLOSED_STATUSES),
                'open_requests_count'
            )
            ->selectSub(
                CrmRequest::query()->selectRaw('count(*)')
                    ->whereColumn('requests.customer_id', 'profiles.id')
                    ->whereNotIn('status', self::CLOSED_STATUSES)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', $now),
                'overdue_requests_count'
            )
            ->selectSub(
                RequestRating::query()->selectRaw('round(avg(stars), 1)')
                    ->whereColumn('request_ratings.customer_id', 'profiles.id'),
                'avg_csat'
            )
            ->selectSub(
                RequestRating::query()->selectRaw('count(*)')
                    ->whereColumn('request_ratings.customer_id', 'profiles.id'),
                'rating_count'
            )
            ->selectSub(
                CrmRequest::query()->selectRaw('max(created_at)')
                    ->whereColumn('requests.customer_id', 'profiles.id'),
                'last_request_at'
            );

        match ($sort) {
            'csat' => $query->orderByDesc('avg_csat'),
            'recent' => $query->orderByDesc('last_request_at')->orderByDesc('last_contact_at'),
            default => $query->orderByDesc('requests_count'),
        };
        $query->orderBy('full_name');

        $customers = $query->paginate(20)->withQueryString();

        $fields = Profile::query()
            ->whereNotNull('business_field')
            ->where('business_field', '!=', '')
            ->distinct()
            ->orderBy('business_field')
            ->pluck('business_field');

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $search, 'field' => $field, 'sort' => $sort],
            'fields' => $fields,
            'kpis' => [
                'total_customers' => (clone $base)->count(),
                'total_requests' => CrmRequest::query()->count(),
                'avg_csat' => ($v = RequestRating::query()->avg('stars')) ? round($v, 1) : null,
                'open_requests' => CrmRequest::query()->whereNotIn('status', self::CLOSED_STATUSES)->count(),
                'overdue_requests' => CrmRequest::query()
                    ->whereNotIn('status', self::CLOSED_STATUSES)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', $now)
                    ->count(),
            ],
        ]);
    }

    /** Customer 360 — staff or the customer themselves may view. */
    public function show(Request $request, Profile $profile)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        if (! $isStaff && $user->id !== $profile->id) {
            abort(403, 'لا تملك صلاحية الاطلاع على هذا الملف.');
        }

        $now = now();

        // Account manager display name.
        $profile->account_manager_name = $profile->account_manager_id
            ? Profile::query()->whereKey($profile->account_manager_id)->value('full_name')
            : null;
        // Internal notes are staff-only.
        if (! $isStaff) {
            $profile->internal_notes = null;
        }

        // All requests + suggestions for this customer (split by category.is_suggestion).
        $allRows = CrmRequest::query()
            ->where('requests.customer_id', $profile->id)
            ->leftJoin('categories', 'categories.id', '=', 'requests.category_id')
            ->leftJoin('products', 'products.id', '=', 'requests.product_id')
            ->leftJoin('profiles as assignee', 'assignee.id', '=', 'requests.assigned_to')
            ->orderByDesc('requests.created_at')
            ->get([
                'requests.id',
                'requests.request_number',
                'requests.title',
                'requests.status',
                'requests.priority',
                'requests.created_at',
                'requests.closed_at',
                'requests.due_at',
                'requests.reopened_count',
                'requests.idea_stage',
                'requests.decision',
                'requests.published_to_customers',
                'categories.name_ar as category_name',
                'categories.is_suggestion as is_suggestion',
                'products.name_ar as product_name',
                'assignee.full_name as assigned_name',
            ]);

        $requests = $allRows->filter(fn ($r) => ! $r->is_suggestion)->values();
        $suggestions = $allRows->filter(fn ($r) => (bool) $r->is_suggestion)->values();

        // ---- Request roll-up stats ----
        $openReq = $requests->filter(fn ($r) => ! in_array($r->status, self::CLOSED_STATUSES, true));
        $overdue = $openReq->filter(fn ($r) => $r->due_at && Carbon::parse($r->due_at)->lt($now))->count();
        $closed = $requests->filter(fn ($r) => in_array($r->status, self::RESOLVED_STATUSES, true))->count();
        $reopened = (int) $requests->sum(fn ($r) => (int) ($r->reopened_count ?? 0));

        $closedTimed = $requests->filter(fn ($r) => $r->closed_at);
        $avgHours = $closedTimed->count()
            ? round($closedTimed->avg(fn ($r) => Carbon::parse($r->created_at)->floatDiffInHours(Carbon::parse($r->closed_at))), 1)
            : null;

        $slaClosed = $requests->filter(fn ($r) => $r->closed_at && $r->due_at);
        $slaPct = $slaClosed->count()
            ? round($slaClosed->filter(fn ($r) => Carbon::parse($r->closed_at)->lte(Carbon::parse($r->due_at)))->count() / $slaClosed->count() * 100, 1)
            : null;

        // ---- Ratings (CSAT) ----
        $ratings = RequestRating::query()
            ->where('request_ratings.customer_id', $profile->id)
            ->leftJoin('requests', 'requests.id', '=', 'request_ratings.request_id')
            ->orderByDesc('request_ratings.created_at')
            ->get([
                'request_ratings.id',
                'request_ratings.stars',
                'request_ratings.notes',
                'request_ratings.created_at',
                'request_ratings.request_id',
                'requests.request_number',
                'requests.title as request_title',
            ]);
        $csat = $ratings->count() ? round($ratings->avg('stars'), 2) : null;
        $promoters = $ratings->filter(fn ($r) => $r->stars >= 4)->count();
        $detractors = $ratings->filter(fn ($r) => $r->stars <= 2)->count();

        // ---- Suggestions engagement (support votes) ----
        $voteCounts = collect();
        if ($suggestions->isNotEmpty()) {
            $voteCounts = SuggestionVote::query()
                ->whereIn('request_id', $suggestions->pluck('id'))
                ->whereIn('vote', ['support', 'strong_support'])
                ->selectRaw('request_id, count(*) as c')
                ->groupBy('request_id')
                ->pluck('c', 'request_id');
        }
        $suggestions = $suggestions->map(function ($s) use ($voteCounts) {
            $s->votes = (int) ($voteCounts[$s->id] ?? 0);

            return $s;
        });

        // ---- Top categories / products ----
        $topCategories = $requests
            ->groupBy(fn ($r) => $r->category_name ?? '—')
            ->map(fn ($grp, $name) => ['name' => $name, 'n' => $grp->count()])
            ->sortByDesc('n')->take(5)->values();
        $topProducts = $requests
            ->filter(fn ($r) => $r->product_name)
            ->groupBy(fn ($r) => $r->product_name)
            ->map(fn ($grp, $name) => ['name' => $name, 'n' => $grp->count()])
            ->sortByDesc('n')->take(5)->values();

        // ---- Related collections ----
        $contacts = CustomerContact::query()
            ->where('customer_id', $profile->id)
            ->orderByDesc('is_primary')
            ->orderBy('full_name')
            ->get();

        $subscriptions = CustomerSubscription::query()
            ->where('customer_id', $profile->id)
            ->orderByDesc('start_date')
            ->get();

        $activationTasks = CustomerActivationTask::query()
            ->where('customer_id', $profile->id)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get(['id', 'title', 'description', 'status', 'due_date', 'completed_at', 'created_at']);

        // Meetings / calendar events (staff + self both may view).
        $meetings = CalendarEvent::query()
            ->where('calendar_events.related_customer_id', $profile->id)
            ->leftJoin('profiles as ev_assignee', 'ev_assignee.id', '=', 'calendar_events.assigned_to')
            ->orderByDesc('calendar_events.starts_at')
            ->limit(100)
            ->get([
                'calendar_events.id',
                'calendar_events.title',
                'calendar_events.description',
                'calendar_events.event_type',
                'calendar_events.starts_at',
                'calendar_events.ends_at',
                'calendar_events.all_day',
                'calendar_events.location',
                'calendar_events.meeting_url',
                'calendar_events.status',
                'calendar_events.visibility',
                'ev_assignee.full_name as assigned_name',
            ]);

        // Staff-only data.
        $activities = collect();
        $attachments = collect();
        if ($isStaff) {
            $activities = CustomerActivity::query()
                ->where('customer_activities.customer_id', $profile->id)
                ->leftJoin('profiles as performer', 'performer.id', '=', 'customer_activities.performed_by')
                ->orderByDesc('customer_activities.occurred_at')
                ->limit(80)
                ->get([
                    'customer_activities.id',
                    'customer_activities.activity_type',
                    'customer_activities.subject',
                    'customer_activities.summary',
                    'customer_activities.occurred_at',
                    'performer.full_name as performed_by_name',
                ]);

            $attachments = CustomerAttachment::query()
                ->where('customer_id', $profile->id)
                ->orderByDesc('created_at')
                ->get();
        }

        return Inertia::render('Customers/Show', [
            'profile' => $profile,
            'isStaff' => $isStaff,
            'requests' => $requests,
            'suggestions' => $suggestions,
            'contacts' => $contacts,
            'subscriptions' => $subscriptions,
            'activities' => $activities,
            'activationTasks' => $activationTasks,
            'meetings' => $meetings,
            'ratings' => $ratings,
            'attachments' => $attachments,
            'topCategories' => $topCategories,
            'topProducts' => $topProducts,
            'stats' => [
                'requests' => [
                    'total' => $requests->count(),
                    'open' => $openReq->count(),
                    'overdue' => $overdue,
                    'closed' => $closed,
                    'reopened' => $reopened,
                    'avg_hours' => $avgHours,
                    'sla_pct' => $slaPct,
                    'first_at' => optional($requests->last())->created_at,
                    'last_at' => optional($requests->first())->created_at,
                ],
                'satisfaction' => [
                    'csat' => $csat,
                    'count' => $ratings->count(),
                    'promoters' => $promoters,
                    'detractors' => $detractors,
                ],
                'suggestions' => [
                    'total' => $suggestions->count(),
                    'accepted' => $suggestions->filter(fn ($s) => $s->decision === 'accepted')->count(),
                    'implemented' => $suggestions->filter(fn ($s) => $s->idea_stage === 'implemented')->count(),
                    'published' => $suggestions->filter(fn ($s) => (bool) $s->published_to_customers)->count(),
                ],
                'contacts' => $contacts->count(),
                'active_subscriptions' => $subscriptions->where('status', 'active')->count(),
            ],
        ]);
    }
}
