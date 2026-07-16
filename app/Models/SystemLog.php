<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_log';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'data' => 'array',
        ];
    }
}
