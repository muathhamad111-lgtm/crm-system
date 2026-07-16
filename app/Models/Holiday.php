<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Holiday extends Model
{
    use HasUuids;

    protected $table = 'holidays';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'block_intake' => 'boolean',
            'created_at' => 'datetime',
            'custom_hours' => 'array',
            'enabled' => 'boolean',
            'end_date' => 'date',
            'exclude_sla' => 'boolean',
            'holiday_date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public function calendar()
    {
        return $this->belongsTo(BusinessCalendar::class, 'calendar_id');
    }
}
