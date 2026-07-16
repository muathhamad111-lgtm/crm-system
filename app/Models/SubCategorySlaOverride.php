<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubCategorySlaOverride extends Model
{
    use HasUuids;

    protected $table = 'sub_category_sla_overrides';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'respect_business_hours' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function businessCalendar()
    {
        return $this->belongsTo(BusinessCalendar::class, 'business_calendar_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(RequestSubCategory::class, 'sub_category_id');
    }
}
