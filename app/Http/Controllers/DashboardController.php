<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Request as CrmRequest;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /** Statuses considered closed/terminal. */
    private const TERMINAL = ['closed', 'completed', 'rejected', 'cancelled'];

    /** Statuses considered "done" for the soft service status. */
    private const DONE = ['closed', 'completed'];

    public function home(Request $request)
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        return $this->index($request);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return $user->isStaff()
            ? $this->staffHome($user)
            : $this->customerHome($user);
    }

    // ======================================================================
    // Customer home — personal dashboard (mirrors Lovable customer branch).
    // ======================================================================
    private function customerHome($user)
    {
        $now = now();

        // Suggestion category ids (excluded from ticket counts everywhere).
        $suggestionCatIds = DB::table('categories')->where('is_suggestion', true)->pluck('id');

        // All of the customer's real requests (non-suggestion), lightweight columns.
        $rows = CrmRequest::query()
            ->where('customer_id', $user->id)
            ->when($suggestionCatIds->isNotEmpty(), fn ($q) => $q->whereNotIn('category_id', $suggestionCatIds))
            ->with('category:id,name_ar,color')
            ->latest('updated_at')
            ->get([
                'id', 'request_number', 'title', 'status', 'priority', 'category_id',
                'created_at', 'updated_at', 'due_at', 'closed_at',
                'returned_to_customer_at', 'return_reason', 'auto_close_due_at',
            ]);

        $isOpen = fn ($r) => ! in_array($this->rawStatus($r), self::TERMINAL, true);
        $open = $rows->filter($isOpen);
        $serviceStatuses = $open->map(fn ($r) => $this->serviceStatus($r, $now));

        $stats = [
            'total' => $rows->count(),
            'open' => $open->count(),
            'awaiting_you' => $serviceStatuses->filter(fn ($s) => $s === 'awaiting_customer')->count(),
            'on_track' => $serviceStatuses->filter(fn ($s) => in_array($s, ['on_track', 'due_soon'], true))->count(),
            'overdue' => $serviceStatuses->filter(fn ($s) => $s === 'overdue')->count(),
            'completed' => $rows->filter(fn ($r) => in_array($this->rawStatus($r), self::DONE, true))->count(),
        ];

        // ---- Action-required aggregation (priority #1) ----
        $actionItems = [];

        // 1. Requests returned to the customer (awaiting their action).
        foreach ($rows->filter(fn ($r) => $this->rawStatus($r) === 'awaiting_customer')->take(5) as $r) {
            $actionItems[] = [
                'key' => 'ret-'.$r->id,
                'kind' => 'return',
                'title' => $r->title,
                'hint' => $r->return_reason ?: 'أكمل البيانات المطلوبة لمتابعة معالجة طلبك',
                'cta' => 'استكمال',
                'url' => "/requests/{$r->id}",
                'ref' => $r->request_number,
                'accent' => 'destructive',
                'icon' => 'reply',
            ];
        }

        // 2. Completed/closed requests without a rating yet.
        $pendingRating = CrmRequest::query()
            ->where('customer_id', $user->id)
            ->whereIn('status', self::DONE)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('request_ratings')
                ->whereColumn('request_ratings.request_id', 'requests.id'))
            ->latest('closed_at')
            ->limit(5)
            ->get(['id', 'request_number', 'title']);
        foreach ($pendingRating as $r) {
            $actionItems[] = [
                'key' => 'rate-'.$r->id,
                'kind' => 'rating',
                'title' => $r->title,
                'hint' => 'أُنجز الطلب — رأيك يساعدنا على التحسّن',
                'cta' => 'قيّم الآن',
                'url' => "/requests/{$r->id}",
                'ref' => $r->request_number,
                'accent' => 'warning',
                'icon' => 'star',
            ];
        }

        // Upcoming appointments (next 14 days), confirmed + pending.
        $appointments = Appointment::query()
            ->where('customer_id', $user->id)
            ->whereIn('status', ['confirmed', 'pending_confirmation'])
            ->whereBetween('starts_at', [$now, (clone $now)->addDays(14)])
            ->with('type:id,name_ar,mode,color,icon_name')
            ->orderBy('starts_at')
            ->limit(6)
            ->get();

        // 3. Pending-confirmation appointments become action items.
        foreach ($appointments->where('status', 'pending_confirmation') as $a) {
            $actionItems[] = [
                'key' => 'appt-'.$a->id,
                'kind' => 'appointment',
                'title' => $a->type?->name_ar ?? 'موعد جديد',
                'hint' => 'يحتاج تأكيدك — '.$this->fmtApptHint($a->starts_at),
                'cta' => 'تأكيد',
                'url' => "/appointments/{$a->id}",
                'ref' => $a->appointment_number,
                'accent' => 'warning',
                'icon' => 'calendar-clock',
            ];
        }

        // 4. Newly published suggestions awaiting this customer's vote/rating.
        if ($suggestionCatIds->isNotEmpty()) {
            $newSuggestions = CrmRequest::query()
                ->whereIn('category_id', $suggestionCatIds)
                ->where('published_to_customers', true)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('suggestion_ratings')
                    ->whereColumn('suggestion_ratings.request_id', 'requests.id')
                    ->where('suggestion_ratings.customer_id', $user->id))
                ->latest('published_at')
                ->limit(4)
                ->get(['id', 'request_number', 'title']);
            foreach ($newSuggestions as $s) {
                $actionItems[] = [
                    'key' => 'sug-'.$s->id,
                    'kind' => 'suggestion',
                    'title' => $s->title,
                    'hint' => 'مقترح منشور بانتظار صوتك',
                    'cta' => 'صوّت',
                    'url' => "/suggestions/{$s->id}",
                    'ref' => $s->request_number,
                    'accent' => 'accent',
                    'icon' => 'lightbulb',
                ];
            }
        }

        // Highlighted (most recently updated) active request.
        $highlightRow = $open->first();
        $highlight = $highlightRow ? [
            'id' => $highlightRow->id,
            'request_number' => $highlightRow->request_number,
            'title' => $highlightRow->title,
            'status' => $this->rawStatus($highlightRow),
            'service_status' => $this->serviceStatus($highlightRow, $now),
            'category' => $highlightRow->category?->name_ar,
            'created_at' => $highlightRow->created_at,
            'updated_at' => $highlightRow->updated_at,
        ] : null;

        // Last 3 requests (any status) for the summary card.
        $last3 = $rows->take(3)->map(fn ($r) => [
            'id' => $r->id,
            'request_number' => $r->request_number,
            'title' => $r->title,
            'category' => $r->category?->name_ar,
            'service_status' => $this->serviceStatus($r, $now),
            'updated_at' => $r->updated_at,
        ])->values();

        // Confirmed upcoming appointments (card list).
        $confirmedAppts = $appointments->where('status', 'confirmed')->take(3)->map(fn ($a) => [
            'id' => $a->id,
            'appointment_number' => $a->appointment_number,
            'type' => $a->type?->name_ar ?? 'موعد',
            'mode' => $a->type?->mode?->value ?? $a->type?->mode,
            'starts_at' => optional($a->starts_at)->toIso8601String(),
            'meeting_url' => $a->meeting_url,
            'location' => $a->location,
        ])->values();

        // Recent updates timeline (comments + status changes, last 30 days).
        $recentUpdates = $this->customerRecentUpdates($user, $now);

        // Support info (business hours + channels).
        $settings = SystemSetting::query()
            ->whereIn('key', ['business_hours', 'support_channels'])
            ->get(['key', 'value'])
            ->keyBy('key');

        return Inertia::render('Dashboard', [
            'branch' => 'customer',
            'name' => $user->profile?->full_name ?? $user->name,
            'stats' => $stats,
            'actionItems' => array_slice($actionItems, 0, 6),
            'actionCount' => count($actionItems),
            'highlight' => $highlight,
            'last3' => $last3,
            'appointments' => $confirmedAppts,
            'recentUpdates' => $recentUpdates,
            'support' => [
                'business_hours' => $settings['business_hours']->value ?? null,
                'support_channels' => $settings['support_channels']->value ?? null,
            ],
        ]);
    }

    // ======================================================================
    // Staff / admin home — operations summary (mirrors Lovable staff branch).
    // ======================================================================
    private function staffHome($user)
    {
        $now = now();
        $isAdmin = $user->isAdmin();

        $suggestionCatIds = DB::table('categories')->where('is_suggestion', true)->pluck('id');

        // Base = all real (non-suggestion) requests.
        $base = fn () => CrmRequest::query()
            ->when($suggestionCatIds->isNotEmpty(), fn ($q) => $q->whereNotIn('category_id', $suggestionCatIds));

        $statusCounts = $base()->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');
        $g = fn (array $ss) => collect($ss)->sum(fn ($s) => (int) ($statusCounts[$s] ?? 0));

        $total = (int) $statusCounts->sum();
        $completed = $g(['completed', 'closed']);
        $active = $total - $completed - $g(['rejected', 'cancelled']);
        $overdue = (int) $base()
            ->whereNotIn('status', self::TERMINAL)
            ->whereNull('sla_paused_at')->whereNotNull('due_at')->where('due_at', '<', $now)->count();
        $unassigned = (int) $base()->whereNotIn('status', self::TERMINAL)->whereNull('assigned_to')->count();

        $csat = DB::table('request_ratings')->avg('stars');
        $ratingCount = (int) DB::table('request_ratings')->count();

        // Resolution SLA compliance (decided = met + breached + overdue).
        $slaRows = $base()->whereNotNull('due_at')->get(['due_at', 'closed_at']);
        $met = 0;
        $decided = 0;
        foreach ($slaRows as $r) {
            if ($r->closed_at) {
                $decided++;
                if ($r->closed_at->lessThanOrEqualTo($r->due_at)) {
                    $met++;
                }
            } elseif ($r->due_at->isPast()) {
                $decided++;
            }
        }
        $slaPct = $decided > 0 ? (int) round(($met / $decided) * 100) : null;

        $kpis = [
            'active' => $active,
            'pending' => $g(['new', 'under_review']),
            'overdue' => $overdue,
            'completed' => $completed,
            'urgent' => (int) $base()->whereNotIn('status', self::TERMINAL)->where('priority', 'urgent')->count(),
            'escalated' => $g(['escalated']),
            'unassigned' => $unassigned,
            'total' => $total,
            'csat' => $csat ? round($csat, 1) : null,
            'rating_count' => $ratingCount,
            'sla' => $slaPct,
        ];

        // Status breakdown (ordered).
        $statusOrder = ['new', 'under_review', 'in_progress', 'awaiting_customer', 'awaiting_internal', 'escalated', 'completed', 'closed', 'rejected', 'reopened'];
        $statusBreakdown = collect($statusOrder)
            ->map(fn ($s) => ['key' => $s, 'value' => (int) ($statusCounts[$s] ?? 0)])
            ->filter(fn ($x) => $x['value'] > 0)
            ->values();

        // Priority breakdown for OPEN requests.
        $prioCounts = $base()->whereNotIn('status', self::TERMINAL)
            ->select('priority', DB::raw('count(*) as c'))->groupBy('priority')->pluck('c', 'priority');
        $priorityBreakdown = collect(['urgent', 'high', 'medium', 'low'])
            ->map(fn ($p) => ['key' => $p, 'value' => (int) ($prioCounts[$p] ?? 0)])
            ->values();

        // ---- Requires action lists ----
        $overdueList = $base()
            ->whereNotIn('status', self::TERMINAL)
            ->whereNull('sla_paused_at')->whereNotNull('due_at')->where('due_at', '<', $now)
            ->orderBy('due_at')
            ->limit(5)
            ->get(['id', 'request_number', 'title', 'status', 'priority', 'due_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'request_number' => $r->request_number,
                'title' => $r->title,
                'priority' => $this->rawPriority($r),
                'due_at' => $r->due_at,
            ]);

        $unassignedList = $base()
            ->whereNotIn('status', self::TERMINAL)->whereNull('assigned_to')
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'request_number', 'title', 'priority', 'created_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'request_number' => $r->request_number,
                'title' => $r->title,
                'priority' => $this->rawPriority($r),
                'created_at' => $r->created_at,
            ]);

        $escalatedList = $base()
            ->where('status', 'escalated')
            ->latest('escalated_at')
            ->limit(5)
            ->get(['id', 'request_number', 'title', 'priority', 'escalated_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'request_number' => $r->request_number,
                'title' => $r->title,
                'priority' => $this->rawPriority($r),
                'escalated_at' => $r->escalated_at,
            ]);

        $lowRatings = DB::table('request_ratings as rr')
            ->join('requests as r', 'r.id', '=', 'rr.request_id')
            ->where('rr.stars', '<=', 2)
            ->orderByDesc('rr.created_at')
            ->limit(5)
            ->get(['rr.id', 'rr.stars', 'rr.notes', 'rr.created_at', 'r.id as request_id', 'r.request_number', 'r.title'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'request_id' => $r->request_id,
                'request_number' => $r->request_number,
                'title' => $r->title,
                'stars' => (int) $r->stars,
                'notes' => $r->notes,
                'created_at' => $r->created_at,
            ]);

        // Recent activity timeline across all real requests.
        $activity = DB::table('request_activity_log as l')
            ->join('requests as r', 'r.id', '=', 'l.request_id')
            ->when($suggestionCatIds->isNotEmpty(), fn ($q) => $q->whereNotIn('r.category_id', $suggestionCatIds))
            ->leftJoin('profiles as p', 'p.id', '=', 'l.user_id')
            ->orderByDesc('l.created_at')
            ->limit(10)
            ->get([
                'l.id', 'l.action', 'l.from_value', 'l.to_value', 'l.created_at',
                'r.id as request_id', 'r.request_number', 'r.title', 'p.full_name as actor',
            ])
            ->map(fn ($a) => [
                'id' => $a->id,
                'action' => $a->action,
                'to_value' => $a->to_value,
                'created_at' => $a->created_at,
                'request_id' => $a->request_id,
                'request_number' => $a->request_number,
                'title' => $a->title,
                'actor' => $a->actor,
            ]);

        // Recent requests (latest updated).
        $recent = $base()
            ->with(['category:id,name_ar,color'])
            ->latest('updated_at')
            ->limit(6)
            ->get(['id', 'request_number', 'title', 'status', 'priority', 'category_id', 'created_at', 'updated_at'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'request_number' => $r->request_number,
                'title' => $r->title,
                'status' => $this->rawStatus($r),
                'priority' => $this->rawPriority($r),
                'category' => $r->category?->name_ar,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);

        return Inertia::render('Dashboard', [
            'branch' => 'staff',
            'name' => $user->profile?->full_name ?? $user->name,
            'isAdmin' => $isAdmin,
            'kpis' => $kpis,
            'statusBreakdown' => $statusBreakdown,
            'priorityBreakdown' => $priorityBreakdown,
            'requiresAction' => [
                'overdue' => $overdueList,
                'unassigned' => $unassignedList,
                'escalated' => $escalatedList,
                'low_ratings' => $lowRatings,
            ],
            'activity' => $activity,
            'recent' => $recent,
        ]);
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    private function rawStatus($r): string
    {
        return $r->status?->value ?? (string) $r->status;
    }

    private function rawPriority($r): string
    {
        return $r->priority?->value ?? (string) $r->priority;
    }

    /** Soft, SLA-free service status shown to customers. */
    private function serviceStatus($r, $now): string
    {
        $status = $this->rawStatus($r);
        if (in_array($status, self::DONE, true)) {
            return 'done';
        }
        if ($status === 'awaiting_customer') {
            return 'awaiting_customer';
        }
        if ($r->due_at) {
            $diffH = $now->diffInHours($r->due_at, false);
            if ($diffH < 0) {
                return 'overdue';
            }
            if ($diffH <= 24) {
                return 'due_soon';
            }
        }

        return 'on_track';
    }

    private function fmtApptHint($startsAt): string
    {
        if (! $startsAt) {
            return '';
        }

        return $startsAt->locale('ar')->translatedFormat('j M · g:i A');
    }

    /** Comments + status-change activity on the customer's requests (last 30 days). */
    private function customerRecentUpdates($user, $now): array
    {
        $reqIds = CrmRequest::query()->where('customer_id', $user->id)->pluck('id');
        if ($reqIds->isEmpty()) {
            return [];
        }
        $since = (clone $now)->subDays(30);
        $meta = DB::table('requests')->whereIn('id', $reqIds)->pluck('title', 'id');
        $numbers = DB::table('requests')->whereIn('id', $reqIds)->pluck('request_number', 'id');

        $comments = DB::table('request_comments')
            ->whereIn('request_id', $reqIds)
            ->where('is_internal', false)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['id', 'request_id', 'body', 'user_id', 'created_at'])
            ->map(fn ($c) => [
                'id' => 'c-'.$c->id,
                'kind' => $c->user_id === $user->id ? 'you_replied' : 'staff_replied',
                'request_id' => $c->request_id,
                'request_number' => $numbers[$c->request_id] ?? null,
                'title' => $meta[$c->request_id] ?? null,
                'summary' => $c->body,
                'created_at' => $c->created_at,
            ]);

        $logs = DB::table('request_activity_log')
            ->whereIn('request_id', $reqIds)
            ->whereIn('action', ['changed_status', 'closed', 'reopened'])
            ->whereNotNull('to_value')
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['id', 'request_id', 'to_value', 'created_at'])
            ->map(fn ($l) => [
                'id' => 'l-'.$l->id,
                'kind' => 'status_change',
                'request_id' => $l->request_id,
                'request_number' => $numbers[$l->request_id] ?? null,
                'title' => $meta[$l->request_id] ?? null,
                'summary' => $l->to_value,
                'created_at' => $l->created_at,
            ]);

        return $comments->concat($logs)
            ->sortByDesc('created_at')
            ->take(8)
            ->values()
            ->all();
    }
}
