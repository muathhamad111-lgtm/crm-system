<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmployeeLeave extends Model
{
    use HasUuids;

    protected $table = 'employee_leaves';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'coverage_applied_at' => 'datetime',
            'coverage_strategy' => \App\Enums\LeaveCoverageStrategy::class,
            'created_at' => 'datetime',
            'end_date' => 'date',
            'impact_snapshot' => 'array',
            'start_date' => 'date',
            'status' => \App\Enums\LeaveStatus::class,
            'updated_at' => 'datetime',
        ];
    }

    public function approver()
    {
        return $this->belongsTo(Profile::class, 'approved_by');
    }

    public function employee()
    {
        return $this->belongsTo(Profile::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function requester()
    {
        return $this->belongsTo(Profile::class, 'requested_by');
    }

    public function substitute()
    {
        return $this->belongsTo(Profile::class, 'substitute_id');
    }
}
