<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestRating extends Model
{
    use HasUuids;

    protected $table = 'request_ratings';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'dissatisfaction_reasons' => 'array',
            'extra_answers' => 'array',
            'needs_supervisor_review' => 'boolean',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    public function ttsCustomer()
    {
        return $this->belongsTo(TtsCustomer::class, 'tts_customer_id');
    }
}
