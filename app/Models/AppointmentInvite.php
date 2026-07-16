<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AppointmentInvite extends Model
{
    use HasUuids;

    protected $table = 'appointment_invites';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
