<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KbArticleVersion extends Model
{
    use HasUuids;

    protected $table = 'kb_article_versions';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function article()
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }
}
