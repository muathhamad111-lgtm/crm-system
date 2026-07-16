<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestComment extends Model
{
    use HasUuids;

    protected $table = 'request_comments';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
            'edited_at' => 'datetime',
            'is_internal' => 'boolean',
            'mentioned_user_ids' => 'array',
            'pinned_at' => 'datetime',
            'read_by' => 'array',
        ];
    }

    public function assignedToUser()
    {
        return $this->belongsTo(Profile::class, 'assigned_to_user_id');
    }

    public function authorTeam()
    {
        return $this->belongsTo(Team::class, 'author_team_id');
    }

    public function mentionedTeam()
    {
        return $this->belongsTo(Team::class, 'mentioned_team_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(RequestComment::class, 'reply_to_id');
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
