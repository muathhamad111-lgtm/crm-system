<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriorityMultiplier extends Model
{
    protected $table = 'priority_multipliers';

    protected $primaryKey = 'priority';
    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'multiplier' => 'decimal:2',
            'priority' => \App\Enums\RequestPriority::class,
            'updated_at' => 'datetime',
        ];
    }
}
