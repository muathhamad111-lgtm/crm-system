<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CustomerSubscription extends Model
{
    use HasUuids;

    protected $table = 'customer_subscriptions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'end_date' => 'date',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
            'start_date' => 'date',
            'status' => \App\Enums\SubscriptionStatus::class,
            'updated_at' => 'datetime',
        ];
    }
}
