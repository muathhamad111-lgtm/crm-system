<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /** Current user's notifications (paginated, filter read/unread). */
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all'); // all | unread | read

        $query = Notification::query()
            ->where('user_id', $user->id)
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($filter === 'read', fn ($q) => $q->whereNotNull('read_at'))
            ->orderByDesc('created_at');

        $notifications = $query->paginate(20)->withQueryString()
            ->through(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'scope' => $n->scope,
                'title' => $n->title,
                'body' => $n->body,
                'link_path' => $n->link_path,
                'priority' => $n->priority,
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->toIso8601String(),
            ]);

        $unreadCount = Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $unreadCount,
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
