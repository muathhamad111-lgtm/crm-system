<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TtsCustomer extends Model
{
    use HasUuids;

    protected $table = 'tts_customers';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
            'foundation_date' => 'date',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
            'updated_at' => 'datetime',
        ];
    }
}
