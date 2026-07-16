<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LeaveActivityLog extends Model
{
    use HasUuids;

    protected $table = 'leave_activity_log';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function leave()
    {
        return $this->belongsTo(EmployeeLeave::class, 'leave_id');
    }
}
