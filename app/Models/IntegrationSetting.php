<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class IntegrationSetting extends Model
{
    use HasUuids;

    protected $table = 'integration_settings';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'created_at' => 'datetime',
            'enabled' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}
