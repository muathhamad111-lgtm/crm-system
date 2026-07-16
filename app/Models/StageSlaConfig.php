<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StageSlaConfig extends Model
{
    use HasUuids;

    protected $table = 'stage_sla_config';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'is_terminal' => 'boolean',
            'pauses_sla' => 'boolean',
            'respect_business_hours' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function businessCalendar()
    {
        return $this->belongsTo(BusinessCalendar::class, 'business_calendar_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
