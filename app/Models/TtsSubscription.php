<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TtsSubscription extends Model
{
    use HasUuids;

    protected $table = 'tts_subscriptions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
            'end_date' => 'date',
            'is_demo' => 'boolean',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
            'start_date' => 'date',
            'updated_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(TtsCustomer::class, 'customer_id');
    }
}
