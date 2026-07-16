<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RolePermission extends Model
{
    use HasUuids;

    protected $table = 'role_permissions';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allowed' => 'boolean',
            'role' => \App\Enums\AppRole::class,
            'updated_at' => 'datetime',
        ];
    }
}
