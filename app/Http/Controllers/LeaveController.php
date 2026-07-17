<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLeave;
use App\Models\LeaveActivityLog;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class LeaveController extends Controller
{
    /** Current employee's leaves + leave types for the register form. */
    public function index(Request $request)
    {
        $user = $request->user();

        $leaves = EmployeeLeave::query()
            ->where('employee_id', $user->id)
            ->with(['leaveType:id,label_ar,color,code', 'substitute:id,full_name'])
            ->orderByDesc('start_date')
            ->get();

        $today = Carbon::today();
        $yearStart = Carbon::create($today->year, 1, 1);

        // The status column is an enum-cast; normalise to its string value.
        $statusOf = fn ($l) => $l->status?->value ?? $l->status;

        $stats = [
            'total' => $leaves->count(),
            'active' => $leaves->filter(fn ($l) => $statusOf($l) === 'active')->count(),
            'open' => $leaves->filter(fn ($l) => in_array($statusOf($l), ['approved', 'active'], true))->count(),
            'upcoming' => $leaves->filter(
                fn ($l) => $statusOf($l) === 'approved' && $l->start_date && $l->start_date->gt($today)
            )->count(),
            'history' => $leaves->filter(fn ($l) => in_array($statusOf($l), ['completed', 'cancelled'], true))->count(),
            'days_used' => (int) $leaves
                ->filter(fn ($l) => in_array($statusOf($l), ['approved', 'active', 'completed'], true)
                    && $l->start_date && $l->start_date->gte($yearStart))
                ->sum(fn ($l) => (int) ($l->duration_days ?? 0)),
            'year' => $today->year,
        ];

        $types = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'label_ar', 'color', 'requires_approval', 'affects_assignment']);

        $substitutes = \App\Models\Profile::query()
            ->where('id', '!=', $user->id)
            ->whereIn('id', function ($q) {
                $q->select('user_id')->from('user_roles')->where('role', '!=', 'customer');
            })
            ->orderBy('full_name')
            ->limit(200)
            ->get(['id', 'full_name']);

        return Inertia::render('Leaves/Index', [
            'leaves' => $leaves,
            'leaveTypes' => $types,
            'substitutes' => $substitutes,
            'stats' => $stats,
        ]);
    }

    /** Register a leave (status pending / approved depending on the type). */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|string|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
            'substitute_id' => 'nullable|string|exists:profiles,id',
            'coverage_strategy' => 'nullable|in:move_all,move_critical_overdue,move_open,manual,none',
        ]);

        $user = $request->user();

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $duration = $start->diffInDays($end) + 1;

        $type = LeaveType::find($validated['leave_type_id']);
        $status = ($type && $type->requires_approval) ? 'pending' : 'approved';

        $leave = EmployeeLeave::create([
            'employee_id' => $user->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'duration_days' => $duration,
            'status' => $status,
            'reason' => $validated['reason'] ?? null,
            'requested_by' => $user->id,
            'substitute_id' => $validated['substitute_id'] ?? null,
            'coverage_strategy' => $validated['coverage_strategy'] ?? 'none',
        ]);

        LeaveActivityLog::create([
            'leave_id' => $leave->id,
            'user_id' => $user->id,
            'action' => 'created',
            'to_value' => $status,
            'notes' => 'تم تسجيل الإجازة',
        ]);

        return back()->with('success', 'تم تسجيل الإجازة');
    }

    /** Cancel one of the current employee's own leaves. */
    public function cancel(Request $request, EmployeeLeave $leave)
    {
        $user = $request->user();

        abort_unless($leave->employee_id === $user->id, 403, 'لا يمكنك إلغاء إجازة موظف آخر.');

        if (in_array($leave->status->value ?? $leave->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'لا يمكن إلغاء هذه الإجازة.');
        }

        $from = $leave->status->value ?? $leave->status;
        $leave->update(['status' => 'cancelled']);

        LeaveActivityLog::create([
            'leave_id' => $leave->id,
            'user_id' => $user->id,
            'action' => 'cancelled',
            'from_value' => $from,
            'to_value' => 'cancelled',
            'notes' => 'تم إلغاء الإجازة من قبل الموظف',
        ]);

        return back()->with('success', 'تم إلغاء الإجازة');
    }
}
