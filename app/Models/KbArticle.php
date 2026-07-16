<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KbArticle extends Model
{
    use HasUuids;

    protected $table = 'kb_articles';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
            'attachments' => 'array',
            'avg_rating' => 'decimal:2',
            'complexity' => \App\Enums\KbComplexity::class,
            'created_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_general' => 'boolean',
            'keywords' => 'array',
            'reference_links' => 'array',
            'status' => \App\Enums\KbArticleStatus::class,
            'steps' => 'array',
            'type' => \App\Enums\KbArticleType::class,
            'updated_at' => 'datetime',
        ];
    }

    public function versions()
    {
        return $this->hasMany(KbArticleVersion::class, 'article_id');
    }

    public function ratings()
    {
        return $this->hasMany(KbArticleRating::class, 'article_id');
    }

    public function feedback()
    {
        return $this->hasMany(KbArticleFeedback::class, 'article_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'kb_article_products', 'article_id', 'product_id');
    }
}
