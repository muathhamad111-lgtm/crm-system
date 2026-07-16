<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KbTicketSuggestion extends Model
{
    use HasUuids;

    protected $table = 'kb_ticket_suggestions';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'dismissed' => 'boolean',
            'matched_on' => 'array',
            'score' => 'decimal:2',
            'suggested_at' => 'datetime',
        ];
    }

    public function article()
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
