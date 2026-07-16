<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApiKey extends Model
{
    use HasUuids;

    protected $table = 'api_keys';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allowed_ips' => 'array',
            'created_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'scopes' => 'array',
        ];
    }
}
