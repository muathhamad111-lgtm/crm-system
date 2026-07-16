<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Appointment extends Model
{
    use HasUuids;

    protected $table = 'appointments';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'ends_at' => 'datetime',
            'proposed_at' => 'datetime',
            'proposed_ends_at' => 'datetime',
            'proposed_starts_at' => 'datetime',
            'starts_at' => 'datetime',
            'status' => \App\Enums\AppointmentStatus::class,
            'updated_at' => 'datetime',
        ];
    }

    public function type()
    {
        return $this->belongsTo(AppointmentType::class, 'type_id');
    }

    public function customer()
    {
        return $this->belongsTo(Profile::class, 'customer_id');
    }
}
