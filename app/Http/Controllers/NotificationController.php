<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /** Current user's notifications (paginated, rich filters). */
    public function index(Request $request)
    {
        $user = $request->user();

        $filter = (string) $request->query('filter', 'all');   // all | unread | read
        $type = (string) $request->query('type', 'all');       // request | appointment | ...
        $priority = (string) $request->query('priority', 'all'); // urgent | important | normal
        $period = (string) $request->query('period', 'all');   // all | today | 7d | 30d
        $search = trim((string) $request->query('q', ''));

        $periodStart = match ($period) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => null,
        };

        $base = Notification::query()->where('user_id', $user->id);

        $query = (clone $base)
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($filter === 'read', fn ($q) => $q->whereNotNull('read_at'))
            ->when($type !== 'all' && $type !== '', fn ($q) => $q->where('type', $type))
            ->when($priority !== 'all' && $priority !== '', fn ($q) => $q->where('priority', $priority))
            ->when($periodStart, fn ($q) => $q->where('created_at', '>=', $periodStart))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(fn ($qq) => $qq->where('title', 'like', $like)->orWhere('body', 'like', $like));
            })
            ->orderByDesc('created_at');

        $notifications = $query->paginate(30)->withQueryString()
            ->through(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'scope' => $n->scope,
                'title' => $n->title,
                'body' => $n->body,
                'link_path' => $n->link_path,
                'request_id' => $n->request_id,
                'priority' => $n->priority,
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->toIso8601String(),
            ]);

        // Per-type counts for the filter chips (unfiltered scope, all of the user's rows).
        $typeCounts = (clone $base)
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'filter' => $filter,
                'type' => $type,
                'priority' => $priority,
                'period' => $period,
                'q' => $search,
            ],
            'unreadCount' => (clone $base)->whereNull('read_at')->count(),
            'totalCount' => (clone $base)->count(),
            'typeCounts' => $typeCounts,
        ]);
    }

    /** Mark a single notification as read. */
    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back();
    }

    /** Mark all of the user's notifications as read. */
    public function markAllRead(Request $request)
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'تم تعليم جميع الإشعارات كمقروءة.');
    }
}
