<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CustomerAttachment extends Model
{
    use HasUuids;

    protected $table = 'customer_attachments';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Profile::class, 'customer_id');
    }
}
