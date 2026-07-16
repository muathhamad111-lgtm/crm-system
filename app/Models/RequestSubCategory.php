<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestSubCategory extends Model
{
    use HasUuids;

    protected $table = 'request_sub_categories';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'allow_tech_bypass' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class, 'sub_category_id');
    }
}
