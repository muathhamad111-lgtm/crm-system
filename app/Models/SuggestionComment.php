<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SuggestionComment extends Model
{
    use HasUuids;

    protected $table = 'suggestion_comments';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
            'kind' => \App\Enums\SuggestionCommentKind::class,
        ];
    }

    public function parent()
    {
        return $this->belongsTo(SuggestionComment::class, 'parent_id');
    }
}
