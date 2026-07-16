<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestExtendedFeedback extends Model
{
    use HasUuids;

    protected $table = 'request_extended_feedback';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'resolved' => 'boolean',
            'would_recommend' => 'boolean',
        ];
    }
}
