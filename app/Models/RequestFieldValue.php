<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestFieldValue extends Model
{
    use HasUuids;

    protected $table = 'request_field_values';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
            'value' => 'array',
        ];
    }

    public function field()
    {
        return $this->belongsTo(FieldDefinition::class, 'field_id');
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
