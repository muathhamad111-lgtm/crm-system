<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KbArticleUsageLog extends Model
{
    use HasUuids;

    protected $table = 'kb_article_usage_log';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'action' => \App\Enums\KbUsageAction::class,
            'context' => 'array',
            'created_at' => 'datetime',
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
