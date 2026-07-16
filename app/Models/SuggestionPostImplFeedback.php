<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SuggestionPostImplFeedback extends Model
{
    use HasUuids;

    protected $table = 'suggestion_post_impl_feedback';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'satisfaction' => \App\Enums\PostImplSatisfaction::class,
            'updated_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
