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
use Illuminate\Support\Facades\Storage;
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
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
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

        // Whitelisted sortable columns (each maps to a real/derived select alias).
        $sortMap = [
            'name' => 'full_name',
            'field' => 'business_field',
            'region' => 'region',
            'requests' => 'requests_count',
            'open' => 'open_requests_count',
            'overdue' => 'overdue_requests_count',
            'csat' => 'avg_csat',
            'recent' => 'last_request_at',
        ];
        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'requests';
        }
        $sortCol = $sortMap[$sort];
        $query->orderBy($sortCol, $dir);
        if ($sortCol !== 'full_name') {
            $query->orderBy('full_name');
        }

        $customers = $query->paginate(20)->withQueryString();

        $fields = Profile::query()
            ->whereNotNull('business_field')
            ->where('business_field', '!=', '')
            ->distinct()
            ->orderBy('business_field')
            ->pluck('business_field');

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $search, 'field' => $field, 'sort' => $sort, 'dir' => $dir],
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
            ->get(['id', 'title', 'description', 'status', 'due_date', 'sort_order', 'assigned_to', 'completed_at', 'created_at']);

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

    // ======================================================================
    // Write / CRUD operations (all gated to staff via route middleware).
    // ======================================================================

    /** Update the core account (profiles) fields + internal notes. */
    public function updateAccount(Request $request, Profile $profile)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'business_field' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'tier' => ['nullable', 'string', 'max:50'],
            'journey_stage' => ['nullable', 'string', 'max:50'],
            'account_status' => ['nullable', 'string', 'max:50'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $profile->update($data);
        $this->logActivity($profile, 'account_updated', 'تحديث بيانات الحساب', null, $request->user()->id);

        return back()->with('success', 'تم تحديث بيانات الحساب');
    }

    /** Dedicated staff-only internal notes save. */
    public function updateNotes(Request $request, Profile $profile)
    {
        $data = $request->validate([
            'internal_notes' => ['nullable', 'string'],
        ]);

        $profile->update(['internal_notes' => $data['internal_notes']]);
        $this->logActivity($profile, 'note_updated', 'تحديث الملاحظات الداخلية', null, $request->user()->id);

        return back()->with('success', 'تم حفظ الملاحظات الداخلية');
    }

    /** Create a contact. */
    public function storeContact(Request $request, Profile $profile)
    {
        $data = $this->contactData($request);
        $data['customer_id'] = $profile->id;
        $data['created_by'] = $request->user()->id;

        $contact = CustomerContact::create($data);
        if ($contact->is_primary) {
            CustomerContact::query()
                ->where('customer_id', $profile->id)
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        $this->logActivity($profile, 'contact_added', 'إضافة جهة تواصل: '.$contact->full_name, null, $request->user()->id);

        return back()->with('success', 'تمت إضافة جهة التواصل');
    }

    /** Update a contact. */
    public function updateContact(Request $request, Profile $profile, CustomerContact $contact)
    {
        $this->ensureBelongs($contact->customer_id, $profile);

        $contact->update($this->contactData($request));
        if ($contact->is_primary) {
            CustomerContact::query()
                ->where('customer_id', $profile->id)
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        $this->logActivity($profile, 'contact_updated', 'تحديث جهة تواصل: '.$contact->full_name, null, $request->user()->id);

        return back()->with('success', 'تم تحديث جهة التواصل');
    }

    /** Delete a contact. */
    public function destroyContact(Request $request, Profile $profile, CustomerContact $contact)
    {
        $this->ensureBelongs($contact->customer_id, $profile);
        $name = $contact->full_name;
        $contact->delete();

        $this->logActivity($profile, 'contact_removed', 'حذف جهة تواصل: '.$name, null, $request->user()->id);

        return back()->with('success', 'تم حذف جهة التواصل');
    }

    /** Create a subscription. */
    public function storeSubscription(Request $request, Profile $profile)
    {
        $data = $this->subscriptionData($request);
        $data['customer_id'] = $profile->id;
        $data['source'] = 'manual';
        $data['last_synced_at'] = now();

        $sub = CustomerSubscription::create($data);
        $this->logActivity($profile, 'subscription_added', 'إضافة اشتراك: '.$sub->product_name, null, $request->user()->id);

        return back()->with('success', 'تمت إضافة الاشتراك');
    }

    /** Update a subscription. */
    public function updateSubscription(Request $request, Profile $profile, CustomerSubscription $subscription)
    {
        $this->ensureBelongs($subscription->customer_id, $profile);

        $subscription->update($this->subscriptionData($request));
        $this->logActivity($profile, 'subscription_updated', 'تحديث اشتراك: '.$subscription->product_name, null, $request->user()->id);

        return back()->with('success', 'تم تحديث الاشتراك');
    }

    /** Delete a subscription. */
    public function destroySubscription(Request $request, Profile $profile, CustomerSubscription $subscription)
    {
        $this->ensureBelongs($subscription->customer_id, $profile);
        $name = $subscription->product_name;
        $subscription->delete();

        $this->logActivity($profile, 'subscription_removed', 'حذف اشتراك: '.$name, null, $request->user()->id);

        return back()->with('success', 'تم حذف الاشتراك');
    }

    /** Create an activation task. */
    public function storeActivationTask(Request $request, Profile $profile)
    {
        $data = $this->activationTaskData($request);
        $data['customer_id'] = $profile->id;
        $data['created_by'] = $request->user()->id;
        $data['completed_at'] = ($data['status'] ?? null) === 'done' ? now() : null;

        $task = CustomerActivationTask::create($data);
        $this->logActivity($profile, 'activation_task_added', 'مهمة تفعيل جديدة: '.$task->title, null, $request->user()->id);

        return back()->with('success', 'تمت إضافة مهمة التفعيل');
    }

    /** Update / toggle an activation task. */
    public function updateActivationTask(Request $request, Profile $profile, CustomerActivationTask $task)
    {
        $this->ensureBelongs($task->customer_id, $profile);

        $data = $this->activationTaskData($request);
        $data['completed_at'] = ($data['status'] ?? null) === 'done' ? ($task->completed_at ?? now()) : null;
        $task->update($data);

        $this->logActivity($profile, 'activation_task_updated', 'تحديث مهمة تفعيل: '.$task->title, null, $request->user()->id);

        return back()->with('success', 'تم تحديث مهمة التفعيل');
    }

    /** Delete an activation task. */
    public function destroyActivationTask(Request $request, Profile $profile, CustomerActivationTask $task)
    {
        $this->ensureBelongs($task->customer_id, $profile);
        $title = $task->title;
        $task->delete();

        $this->logActivity($profile, 'activation_task_removed', 'حذف مهمة تفعيل: '.$title, null, $request->user()->id);

        return back()->with('success', 'تم حذف مهمة التفعيل');
    }

    /** Log a manual activity entry. */
    public function storeActivity(Request $request, Profile $profile)
    {
        $data = $request->validate([
            'activity_type' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
        ]);

        CustomerActivity::create([
            'customer_id' => $profile->id,
            'activity_type' => $data['activity_type'],
            'subject' => $data['subject'],
            'summary' => $data['summary'] ?? null,
            'occurred_at' => now(),
            'performed_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'تم تسجيل النشاط');
    }

    /** Upload an attachment. */
    public function storeAttachment(Request $request, Profile $profile)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480'], // 20 MB
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $path = $file->store("customers/{$profile->id}", 'public');

        $attachment = CustomerAttachment::create([
            'customer_id' => $profile->id,
            'category' => $data['category'] ?? 'general',
            'file_name' => $file->getClientOriginalName(),
            'storage_path' => '/storage/'.$path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'description' => $data['description'] ?? null,
            'uploaded_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->logActivity($profile, 'attachment_added', 'رفع مرفق: '.$attachment->file_name, null, $request->user()->id);

        return back()->with('success', 'تم رفع المرفق');
    }

    /** Delete an attachment (file + row). */
    public function destroyAttachment(Request $request, Profile $profile, CustomerAttachment $attachment)
    {
        $this->ensureBelongs($attachment->customer_id, $profile);

        if ($attachment->storage_path && str_starts_with($attachment->storage_path, '/storage/')) {
            Storage::disk('public')->delete(substr($attachment->storage_path, strlen('/storage/')));
        }
        $name = $attachment->file_name;
        $attachment->delete();

        $this->logActivity($profile, 'attachment_removed', 'حذف مرفق: '.$name, null, $request->user()->id);

        return back()->with('success', 'تم حذف المرفق');
    }

    // ----------------------------------------------------------------------
    // Write helpers
    // ----------------------------------------------------------------------

    /** Validate + normalize contact payload. */
    private function contactData(Request $request): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'role_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
        $data['is_primary'] = $request->boolean('is_primary');
        $data['has_portal_access'] = $request->boolean('has_portal_access');

        return $data;
    }

    /** Validate subscription payload. */
    private function subscriptionData(Request $request): array
    {
        return $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'plan_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,expired,cancelled,trial,suspended'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);
    }

    /** Validate activation-task payload. */
    private function activationTaskData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:todo,in_progress,blocked,done,cancelled'],
            'due_date' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'string', 'exists:profiles,id'],
        ]);
    }

    /** Guard that a child row belongs to the given customer profile. */
    private function ensureBelongs(?string $ownerId, Profile $profile): void
    {
        if ($ownerId !== $profile->id) {
            abort(404);
        }
    }

    /** Insert a customer_activities audit row. */
    private function logActivity(Profile $profile, string $type, string $subject, ?string $summary, string $performedBy): void
    {
        CustomerActivity::create([
            'customer_id' => $profile->id,
            'activity_type' => $type,
            'subject' => $subject,
            'summary' => $summary,
            'occurred_at' => now(),
            'performed_by' => $performedBy,
            'created_at' => now(),
        ]);
    }
}
