<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CustomerActivity extends Model
{
    use HasUuids;

    protected $table = 'customer_activities';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'occurred_at' => 'datetime',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    public function customer()
    {
        return $this->belongsTo(Profile::class, 'customer_id');
    }
}
