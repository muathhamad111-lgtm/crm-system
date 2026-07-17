<?php

namespace App\Http\Controllers;

use App\Models\StoreContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreContactController extends Controller
{
    /** Public store "contact us" messages inbox with a status filter. */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $sort = (string) $request->query('sort', 'date');
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        // Whitelisted sortable columns.
        $sortMap = [
            'sender' => 'first_name',
            'email' => 'email',
            'service' => 'service_type',
            'status' => 'status',
            'date' => 'created_at',
        ];
        if (! array_key_exists($sort, $sortMap)) {
            $sort = 'date';
        }

        $query = StoreContactMessage::query()
            ->when(
                in_array($status, ['new', 'read', 'handled', 'archived'], true),
                fn ($q) => $q->where('status', $status)
            )
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('mobile', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->orderBy($sortMap[$sort], $dir);

        $messages = $query->paginate(25)->withQueryString();

        $base = StoreContactMessage::query();
        $kpis = [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('status', 'new')->count(),
            'read' => (clone $base)->where('status', 'read')->count(),
            'handled' => (clone $base)->where('status', 'handled')->count(),
            'archived' => (clone $base)->where('status', 'archived')->count(),
        ];

        return Inertia::render('StoreInbox/Index', [
            'messages' => $messages,
            'filters' => ['q' => $search, 'status' => $status, 'sort' => $sort, 'dir' => $dir],
            'kpis' => $kpis,
        ]);
    }

    /** Change a message status (+ handled_by/handled_at) and optional internal note. */
    public function updateStatus(Request $request, StoreContactMessage $message)
    {
        $data = $request->validate([
            'status' => 'required|in:new,read,handled,archived',
            'internal_note' => 'nullable|string|max:5000',
        ]);

        $message->status = $data['status'];

        if (array_key_exists('internal_note', $data)) {
            $message->internal_note = $data['internal_note'];
        }

        if ($data['status'] === 'handled') {
            $message->handled_by = $request->user()->id;
            $message->handled_at = now();
        }

        $message->save();

        return back()->with('success', 'تم تحديث حالة الرسالة.');
    }
}
