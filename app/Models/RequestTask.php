<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestTask extends Model
{
    use HasUuids;

    protected $table = 'request_tasks';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assigned_team' => \App\Enums\TargetTeam::class,
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'due_at' => 'datetime',
            'paused_at' => 'datetime',
            'priority' => \App\Enums\TaskPriority::class,
            'reopened_at' => 'datetime',
            'sla_extension_applied' => 'boolean',
            'sla_impact' => \App\Enums\TaskSlaImpact::class,
            'started_at' => 'datetime',
            'status' => \App\Enums\TaskStatus::class,
            'task_type' => \App\Enums\TaskType::class,
            'updated_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function checklist()
    {
        return $this->hasMany(RequestTaskChecklist::class, 'task_id');
    }

    public function comments()
    {
        return $this->hasMany(RequestTaskComment::class, 'task_id');
    }

    public function attachments()
    {
        return $this->hasMany(RequestTaskAttachment::class, 'task_id');
    }
}
