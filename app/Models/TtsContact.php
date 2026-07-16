<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TtsContact extends Model
{
    use HasUuids;

    protected $table = 'tts_contacts';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'delegate_confirmed' => 'boolean',
            'deleted_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(TtsCustomer::class, 'customer_id');
    }
}
