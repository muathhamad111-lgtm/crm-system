<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KbArticleFeedback extends Model
{
    use HasUuids;

    protected $table = 'kb_article_feedback';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'was_helpful' => 'boolean',
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
