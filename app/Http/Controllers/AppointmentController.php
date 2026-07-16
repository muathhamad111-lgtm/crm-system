<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentActivityLog;
use App\Models\AppointmentAvailability;
use App\Models\AppointmentType;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    /** Reason codes surfaced in the booking wizard (mirrors the original portal). */
    private const REASONS = [
        ['value' => 'discuss_request', 'label' => 'مناقشة طلب قائم', 'desc' => 'متابعة أو توضيح حول طلب مفتوح لديك'],
        ['value' => 'tech_support', 'label' => 'دعم أو توضيح فني', 'desc' => 'مساعدة فنية أو شرح خطوة معينة'],
        ['value' => 'training', 'label' => 'تدريب على المنصة', 'desc' => 'جلسة تعريفية أو تدريبية على الاستخدام'],
        ['value' => 'activation', 'label' => 'متابعة تفعيل أو تشغيل', 'desc' => 'مراجعة حالة تفعيل خدمة أو منتج'],
        ['value' => 'suggestion', 'label' => 'مناقشة مقترح أو تحسين', 'desc' => 'تطوير فكرة أو اقتراح تحسين'],
        ['value' => 'onsite_visit', 'label' => 'زيارة ميدانية', 'desc' => 'زيارة موقعك أو فرعك'],
        ['value' => 'other', 'label' => 'سبب آخر', 'desc' => 'اكتب السبب بنفسك'],
    ];

    private const REASON_LABELS = [
        'discuss_request' => 'مناقشة طلب قائم',
        'tech_support' => 'دعم أو توضيح فني',
        'training' => 'تدريب على المنصة',
        'activation' => 'متابعة تفعيل أو تشغيل',
        'suggestion' => 'مناقشة مقترح أو تحسين',
        'onsite_visit' => 'زيارة ميدانية',
        'other' => 'سبب آخر',
    ];

    /** Customer's own appointments (upcoming / past). */
    public function index(Request $request)
    {
        $user = $request->user();

        $appointments = Appointment::query()
            ->with('type:id,name_ar,mode,icon_name,color,duration_minutes')
            ->where('customer_id', $user->id)
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn ($a) => $this->present($a));

        $now = now();
        $upcoming = $appointments->filter(fn ($a) => $a['starts_at'] >= $now->toIso8601String()
            && ! in_array($a['status'], ['cancelled', 'completed', 'no_show'], true))->values();
        $past = $appointments->reject(fn ($a) => in_array($a['id'], $upcoming->pluck('id')->all(), true))->values();

        return Inertia::render('Appointments/Index', [
            'upcoming' => $upcoming,
            'past' => $past,
            'stats' => [
                'total' => $appointments->count(),
                'upcoming' => $upcoming->count(),
                'confirmed' => $appointments->where('status', 'confirmed')->count(),
                'pending' => $appointments->where('status', 'pending_confirmation')->count(),
            ],
        ]);
    }

    /** 4-step wizard data: active types + generated slots for the next 14 days. */
    public function create(Request $request)
    {
        $user = $request->user();

        $types = AppointmentType::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'code', 'name_ar', 'mode', 'duration_minutes', 'buffer_minutes',
                'requires_approval', 'requires_request_link', 'description', 'icon_name', 'color']);

        // Customer's open requests (for the optional "linked request" step).
        $openRequests = DB::table('requests')
            ->where('customer_id', $user->id)
            ->whereNotIn('status', ['closed', 'rejected', 'cancelled', 'completed'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'request_number', 'title']);

        return Inertia::render('Appointments/New', [
            'types' => $types,
            'reasons' => self::REASONS,
            'openRequests' => $openRequests,
            'slotsByType' => $this->slotsForTypes($types),
        ]);
    }

    /** Create an appointment. */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'type_id' => ['required', 'string', 'exists:appointment_types,id'],
            'reason_code' => ['required', 'string', 'max:64'],
            'reason_other' => ['nullable', 'string', 'max:500'],
            'related_request_id' => ['nullable', 'string', 'exists:requests,id'],
            'starts_at' => ['required', 'date'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $type = AppointmentType::findOrFail($data['type_id']);

        if ($type->requires_request_link && empty($data['related_request_id'])) {
            return back()->withErrors(['related_request_id' => 'هذا النوع من المواعيد يتطلب ربطه بطلب قائم.']);
        }

        $starts = Carbon::parse($data['starts_at']);
        $ends = (clone $starts)->addMinutes((int) $type->duration_minutes);
        $status = $type->requires_approval ? 'pending_confirmation' : 'confirmed';

        $appointment = Appointment::create([
            'appointment_number' => $this->nextNumber(),
            'type_id' => $type->id,
            'customer_id' => $user->id,
            'related_request_id' => $data['related_request_id'] ?? null,
            'status' => $status,
            'reason_code' => $data['reason_code'],
            'reason_other' => $data['reason_other'] ?? null,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'duration_minutes' => $type->duration_minutes,
            'customer_notes' => $data['customer_notes'] ?? null,
            'reschedule_count' => 0,
            'created_by' => $user->id,
        ]);

        $this->log($appointment, $user->id, 'created', null, $status, null, ['reason_code' => $data['reason_code']]);

        $this->notify($user->id, "تم إنشاء موعد {$appointment->appointment_number}",
            $status === 'confirmed' ? 'تم تأكيد موعدك.' : 'موعدك بانتظار التأكيد من الفريق.',
            '/appointments/'.$appointment->id, $appointment->id);

        return redirect()->route('appointments.show', $appointment->id)
            ->with('success', 'تم حجز الموعد بنجاح.');
    }

    /** Appointment detail + type + activity log + gated action flags. */
    public function show(Request $request, Appointment $appointment)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();

        abort_unless($isStaff || $appointment->customer_id === $user->id, 403);

        $appointment->load('type', 'customer:id,full_name,email,phone');

        $activity = AppointmentActivityLog::query()
            ->where('appointment_id', $appointment->id)
            ->orderByDesc('created_at')
            ->get(['id', 'action', 'old_status', 'new_status', 'notes', 'metadata', 'created_at']);

        return Inertia::render('Appointments/Show', [
            'appointment' => $this->present($appointment, true),
            'activity' => $activity,
            'can' => $this->gates($appointment, $user, $isStaff),
        ]);
    }

    /** Customer/staff cancel. */
    public function cancel(Request $request, Appointment $appointment)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        abort_unless($isStaff || $appointment->customer_id === $user->id, 403);

        if (in_array($appointment->status->value, ['cancelled', 'completed', 'no_show'], true)) {
            return back()->withErrors(['status' => 'لا يمكن إلغاء هذا الموعد في حالته الحالية.']);
        }

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;
        $old = $appointment->status->value;

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
        ]);

        $this->log($appointment, $user->id, 'cancelled', $old, 'cancelled', $reason);
        $this->notify($appointment->customer_id, "تم إلغاء الموعد {$appointment->appointment_number}", $reason,
            '/appointments/'.$appointment->id, $appointment->id);

        return back()->with('success', 'تم إلغاء الموعد.');
    }

    /** Customer reschedule request / staff reschedule (moves to a new slot). */
    public function reschedule(Request $request, Appointment $appointment)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();
        abort_unless($isStaff || $appointment->customer_id === $user->id, 403);

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (in_array($appointment->status->value, ['cancelled', 'completed', 'no_show'], true)) {
            return back()->withErrors(['status' => 'لا يمكن إعادة جدولة هذا الموعد.']);
        }

        $type = $appointment->type ?: AppointmentType::find($appointment->type_id);
        $starts = Carbon::parse($data['starts_at']);
        $ends = (clone $starts)->addMinutes((int) ($appointment->duration_minutes ?: ($type->duration_minutes ?? 30)));
        $old = $appointment->status->value;

        $appointment->update([
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => $isStaff ? 'confirmed' : 'pending_confirmation',
            'reschedule_count' => (int) $appointment->reschedule_count + 1,
            'last_reschedule_reason' => $data['reason'] ?? null,
        ]);

        $this->log($appointment, $user->id, 'rescheduled', $old, $appointment->status->value, $data['reason'] ?? null,
            ['starts_at' => $starts->toIso8601String()]);
        $this->notify($appointment->customer_id, "تم إعادة جدولة الموعد {$appointment->appointment_number}", null,
            '/appointments/'.$appointment->id, $appointment->id);

        return back()->with('success', 'تم تحديث موعدك.');
    }

    /** Staff confirm. */
    public function confirm(Request $request, Appointment $appointment)
    {
        $user = $request->user();
        abort_unless($user->isStaff(), 403);

        $old = $appointment->status->value;
        $appointment->update(['status' => 'confirmed']);

        $this->log($appointment, $user->id, 'confirmed', $old, 'confirmed');
        $this->notify($appointment->customer_id, "تم تأكيد موعدك {$appointment->appointment_number}", null,
            '/appointments/'.$appointment->id, $appointment->id);

        return back()->with('success', 'تم تأكيد الموعد.');
    }

    /** Staff reject / decline a pending appointment. */
    public function reject(Request $request, Appointment $appointment)
    {
        $user = $request->user();
        abort_unless($user->isStaff(), 403);

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;
        $old = $appointment->status->value;

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
        ]);

        $this->log($appointment, $user->id, 'rejected', $old, 'cancelled', $reason);
        $this->notify($appointment->customer_id, "تعذّر تأكيد موعدك {$appointment->appointment_number}", $reason,
            '/appointments/'.$appointment->id, $appointment->id);

        return back()->with('success', 'تم رفض طلب الموعد.');
    }

    /** Staff management view of ALL appointments. */
    public function manage(Request $request)
    {
        $status = $request->query('status');

        $query = Appointment::query()
            ->with(['type:id,name_ar,mode', 'customer:id,full_name,email'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('starts_at');

        $appointments = $query->limit(200)->get()->map(fn ($a) => $this->present($a, true));

        $counts = Appointment::query()
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        return Inertia::render('Appointments/Manage', [
            'appointments' => $appointments,
            'filter' => $status ?: 'all',
            'stats' => [
                'total' => (int) $counts->sum(),
                'pending' => (int) ($counts['pending_confirmation'] ?? 0),
                'confirmed' => (int) ($counts['confirmed'] ?? 0),
                'completed' => (int) ($counts['completed'] ?? 0),
                'cancelled' => (int) ($counts['cancelled'] ?? 0),
                'no_show' => (int) ($counts['no_show'] ?? 0),
            ],
        ]);
    }

    /* ----------------------------- helpers ----------------------------- */

    /** Present an appointment row for the frontend. */
    private function present(Appointment $a, bool $full = false): array
    {
        $type = $a->relationLoaded('type') ? $a->type : null;

        $row = [
            'id' => $a->id,
            'appointment_number' => $a->appointment_number,
            'status' => $a->status?->value,
            'reason_code' => $a->reason_code,
            'reason_label' => self::REASON_LABELS[$a->reason_code] ?? $a->reason_code,
            'reason_other' => $a->reason_other,
            'starts_at' => optional($a->starts_at)->toIso8601String(),
            'ends_at' => optional($a->ends_at)->toIso8601String(),
            'duration_minutes' => $a->duration_minutes,
            'location' => $a->location,
            'meeting_url' => $a->meeting_url,
            'type' => $type ? [
                'id' => $type->id,
                'name_ar' => $type->name_ar,
                'mode' => $type->mode?->value ?? $type->mode,
                'icon_name' => $type->icon_name ?? null,
                'color' => $type->color ?? null,
            ] : null,
        ];

        if ($full) {
            $row['customer_notes'] = $a->customer_notes;
            $row['staff_notes'] = $a->staff_notes;
            $row['cancellation_reason'] = $a->cancellation_reason;
            $row['reschedule_count'] = $a->reschedule_count;
            $row['created_at'] = optional($a->created_at)->toIso8601String();
            $customer = $a->relationLoaded('customer') ? $a->customer : null;
            $row['customer'] = $customer ? [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone' => $customer->phone ?? null,
            ] : null;
        }

        return $row;
    }

    /** Gated action flags for the detail view. */
    private function gates(Appointment $a, $user, bool $isStaff): array
    {
        $status = $a->status?->value;
        $future = $a->starts_at && $a->starts_at->isFuture();
        $activeState = in_array($status, ['pending_confirmation', 'confirmed'], true);

        return [
            'cancel' => $activeState && ($isStaff || $a->customer_id === $user->id),
            'reschedule' => $activeState && $future && ($isStaff || $a->customer_id === $user->id),
            'confirm' => $isStaff && $status === 'pending_confirmation',
            'reject' => $isStaff && $status === 'pending_confirmation',
            'manage' => $isStaff,
        ];
    }

    /** Generate morning/afternoon/evening slot buckets for each type over the next 14 days. */
    private function slotsForTypes($types): array
    {
        $out = [];

        // All active availability windows keyed by day_of_week.
        $windows = AppointmentAvailability::query()
            ->where('is_active', true)
            ->get(['day_of_week', 'start_time', 'end_time']);

        // Booked appointment ranges (exclude cancelled/no_show/rescheduled).
        $booked = Appointment::query()
            ->whereNotIn('status', ['cancelled', 'no_show', 'rescheduled'])
            ->where('starts_at', '>=', now()->startOfDay())
            ->get(['starts_at', 'ends_at'])
            ->map(fn ($b) => [$b->starts_at?->timestamp, $b->ends_at?->timestamp])
            ->filter(fn ($p) => $p[0] && $p[1])
            ->values()
            ->all();

        $interval = 30; // minutes between candidate starts
        $now = now();

        foreach ($types as $type) {
            $duration = (int) $type->duration_minutes + (int) ($type->buffer_minutes ?? 0);
            $morning = [];
            $afternoon = [];
            $evening = [];

            for ($d = 0; $d < 14; $d++) {
                $day = $now->copy()->addDays($d)->startOfDay();
                $dow = (int) $day->dayOfWeek; // 0=Sunday .. 6=Saturday (matches seed data)
                $dayWindows = $windows->where('day_of_week', $dow);

                foreach ($dayWindows as $w) {
                    $startMin = $this->timeToMinutes($w->start_time);
                    $endMin = $this->timeToMinutes($w->end_time);

                    for ($m = $startMin; $m + $duration <= $endMin; $m += $interval) {
                        $s = $day->copy()->addMinutes($m);
                        if ($s->lte($now)) {
                            continue;
                        }
                        $e = $s->copy()->addMinutes($duration);

                        // Skip if it overlaps an existing booking.
                        $overlaps = false;
                        foreach ($booked as [$bs, $be]) {
                            if ($s->timestamp < $be && $e->timestamp > $bs) {
                                $overlaps = true;
                                break;
                            }
                        }
                        if ($overlaps) {
                            continue;
                        }

                        $slot = ['starts_at' => $s->toIso8601String(), 'ends_at' => $e->toIso8601String()];
                        $h = (int) $s->format('G');
                        if ($h < 12) {
                            $morning[] = $slot;
                        } elseif ($h < 17) {
                            $afternoon[] = $slot;
                        } else {
                            $evening[] = $slot;
                        }
                    }
                }
            }

            $out[$type->id] = [
                'morning' => $morning,
                'afternoon' => $afternoon,
                'evening' => $evening,
            ];
        }

        return $out;
    }

    private function timeToMinutes(?string $t): int
    {
        if (! $t) {
            return 0;
        }
        [$h, $m] = array_pad(explode(':', $t), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }

    /** Auto appointment number APT-YY-N (sequential within the year). */
    private function nextNumber(): string
    {
        $yy = now()->format('y');
        $prefix = "APT-{$yy}-";

        $last = Appointment::query()
            ->where('appointment_number', 'like', $prefix.'%')
            ->orderByDesc('created_at')
            ->value('appointment_number');

        $n = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $mm)) {
            $n = ((int) $mm[1]) + 1;
        }

        return $prefix.$n;
    }

    private function log(Appointment $a, ?string $actorId, string $action, ?string $old, ?string $new, ?string $notes = null, ?array $meta = null): void
    {
        AppointmentActivityLog::create([
            'id' => (string) Str::uuid(),
            'appointment_id' => $a->id,
            'actor_id' => $actorId,
            'action' => $action,
            'old_status' => $old,
            'new_status' => $new,
            'notes' => $notes,
            'metadata' => $meta,
            'created_at' => now(),
        ]);
    }

    private function notify(?string $userId, string $title, ?string $body, ?string $link, ?string $requestId = null): void
    {
        if (! $userId) {
            return;
        }
        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'scope' => 'personal',
            'type' => 'appointment',
            'title' => $title,
            'body' => $body,
            'link_path' => $link,
            'priority' => 'normal',
            'created_at' => now(),
        ]);
    }
}
