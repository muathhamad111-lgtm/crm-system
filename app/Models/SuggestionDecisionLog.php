<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SuggestionDecisionLog extends Model
{
    use HasUuids;

    protected $table = 'suggestion_decisions_log';

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

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
