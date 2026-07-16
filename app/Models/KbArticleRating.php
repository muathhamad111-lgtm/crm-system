<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KbArticleRating extends Model
{
    use HasUuids;

    protected $table = 'kb_article_ratings';

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

    public function article()
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }
}
