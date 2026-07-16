<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TtsProductKey extends Model
{
    use HasUuids;

    protected $table = 'tts_product_keys';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function kindlyProduct()
    {
        return $this->belongsTo(Product::class, 'kindly_product_id');
    }
}
