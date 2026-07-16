<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapabilityMeta extends Model
{
    protected $table = 'capability_meta';

    protected $primaryKey = 'capability';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
