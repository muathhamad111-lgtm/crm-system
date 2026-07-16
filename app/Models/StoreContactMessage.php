<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StoreContactMessage extends Model
{
    use HasUuids;

    protected $table = 'store_contact_messages';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'handled_at' => 'datetime',
        ];
    }

    public function handler()
    {
        return $this->belongsTo(Profile::class, 'handled_by');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
