<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SmsCustomerTemplate extends Model
{
    use HasUuids;

    protected $table = 'sms_customer_templates';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'enabled' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}
