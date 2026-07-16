<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KbGapReport extends Model
{
    use HasUuids;

    protected $table = 'kb_gap_reports';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'keywords' => 'array',
            'related_request_ids' => 'array',
            'updated_at' => 'datetime',
        ];
    }
}
