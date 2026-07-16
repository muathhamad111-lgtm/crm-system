<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;

    protected $table = 'products';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'created_at' => 'datetime',
            'escalation_to_role' => \App\Enums\AppRole::class,
            'restricted' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}
