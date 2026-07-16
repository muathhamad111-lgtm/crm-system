<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CategorySlaOverride extends Model
{
    use HasUuids;

    protected $table = 'category_sla_overrides';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'business_hours_only' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function calendar()
    {
        return $this->belongsTo(BusinessCalendar::class, 'calendar_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
