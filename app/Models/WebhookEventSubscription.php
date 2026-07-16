<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WebhookEventSubscription extends Model
{
    use HasUuids;

    protected $table = 'webhook_event_subscriptions';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'enabled' => 'boolean',
        ];
    }
}
