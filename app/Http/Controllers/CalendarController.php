<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CalendarController extends Controller
{
    /** Staff calendar — list/agenda of events. */
    public function index(Request $request)
    {
        $type = $request->query('type');
        $status = $request->query('status');

        $events = CalendarEvent::query()
            ->when($type && $type !== 'all', fn ($q) => $q->where('event_type', $type))
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderBy('starts_at')
            ->limit(300)
            ->get()
            ->map(fn ($e) => $this->present($e));

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'filters' => [
                'type' => $type ?: 'all',
                'status' => $status ?: 'all',
            ],
            'stats' => [
                'total' => $events->count(),
                'scheduled' => $events->where('status', 'scheduled')->count(),
                'completed' => $events->where('status', 'completed')->count(),
                'today' => $events->filter(fn ($e) => $e['starts_at'] >= now()->startOfDay()->toIso8601String()
                    && $e['starts_at'] <= now()->endOfDay()->toIso8601String())->count(),
                'upcoming' => $events->filter(fn ($e) => $e['starts_at'] >= now()->toIso8601String())->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEvent($request);
        $user = $request->user();

        $event = CalendarEvent::create(array_merge($data, [
            'id' => (string) Str::uuid(),
            'created_by' => $user->id,
        ]));

        return redirect()->route('calendar.index')->with('success', 'تم إنشاء الحدث.');
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $data = $this->validateEvent($request);
        $event->update($data);

        return redirect()->route('calendar.index')->with('success', 'تم تحديث الحدث.');
    }

    public function destroy(Request $request, CalendarEvent $event)
    {
        $event->delete();

        return redirect()->route('calendar.index')->with('success', 'تم حذف الحدث.');
    }

    private function validateEvent(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'in:visit,meeting,call,reminder,task,other'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,cancelled,rescheduled'],
            'visibility' => ['nullable', 'string', 'in:internal,shared'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['nullable', 'boolean'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'string', 'max:500'],
        ]);

        return [
            'title' => $data['title'],
            'event_type' => $data['event_type'],
            'status' => $data['status'] ?? 'scheduled',
            'visibility' => $data['visibility'] ?? 'internal',
            'starts_at' => Carbon::parse($data['starts_at']),
            'ends_at' => Carbon::parse($data['ends_at']),
            'all_day' => (bool) ($data['all_day'] ?? false),
            'reminder_minutes_before' => $data['reminder_minutes_before'] ?? null,
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'meeting_url' => $data['meeting_url'] ?? null,
        ];
    }

    private function present(CalendarEvent $e): array
    {
        return [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'event_type' => $e->event_type?->value ?? $e->event_type,
            'status' => $e->status?->value ?? $e->status,
            'visibility' => $e->visibility?->value ?? $e->visibility,
            'starts_at' => optional($e->starts_at)->toIso8601String(),
            'ends_at' => optional($e->ends_at)->toIso8601String(),
            'all_day' => (bool) $e->all_day,
            'reminder_minutes_before' => $e->reminder_minutes_before,
            'location' => $e->location,
            'meeting_url' => $e->meeting_url,
        ];
    }
}
