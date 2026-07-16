<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BusinessCalendar extends Model
{
    use HasUuids;

    protected $table = 'business_calendars';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'is_default' => 'boolean',
            'seasonal_schedule' => 'array',
            'updated_at' => 'datetime',
            'weekly_schedule' => 'array',
        ];
    }
}
