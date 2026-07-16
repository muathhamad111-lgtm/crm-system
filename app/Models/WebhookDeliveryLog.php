<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WebhookDeliveryLog extends Model
{
    use HasUuids;

    protected $table = 'webhook_delivery_log';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'delivered_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'payload' => 'array',
            'updated_at' => 'datetime',
        ];
    }
}
