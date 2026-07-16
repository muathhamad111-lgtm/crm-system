<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CustomerContact extends Model
{
    use HasUuids;

    protected $table = 'customer_contacts';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'communication_preferences' => 'array',
            'created_at' => 'datetime',
            'has_portal_access' => 'boolean',
            'is_primary' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Profile::class, 'customer_id');
    }
}
