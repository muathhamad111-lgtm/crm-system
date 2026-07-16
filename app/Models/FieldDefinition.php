<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FieldDefinition extends Model
{
    use HasUuids;

    protected $table = 'field_definitions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'is_active' => 'boolean',
            'options' => 'array',
            'required' => 'boolean',
            'updated_at' => 'datetime',
            'visible_to_customer' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
