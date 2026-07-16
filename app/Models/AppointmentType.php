<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AppointmentType extends Model
{
    use HasUuids;

    protected $table = 'appointment_types';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'is_active' => 'boolean',
            'mode' => \App\Enums\AppointmentMode::class,
            'requires_approval' => 'boolean',
            'requires_request_link' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}
