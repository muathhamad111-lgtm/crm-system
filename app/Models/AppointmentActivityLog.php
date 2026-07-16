<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AppointmentActivityLog extends Model
{
    use HasUuids;

    protected $table = 'appointment_activity_log';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'metadata' => 'array',
            'new_status' => \App\Enums\AppointmentStatus::class,
            'old_status' => \App\Enums\AppointmentStatus::class,
        ];
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
