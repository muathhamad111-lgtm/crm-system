<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AppointmentTypeAssignee extends Model
{
    use HasUuids;

    protected $table = 'appointment_type_assignees';

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

    public function type()
    {
        return $this->belongsTo(AppointmentType::class, 'type_id');
    }
}
