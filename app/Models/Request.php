<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Request extends Model
{
    use HasUuids;

    protected $table = 'requests';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assigned_team' => \App\Enums\TargetTeam::class,
            'auto_close_due_at' => 'datetime',
            'channel' => \App\Enums\RequestChannel::class,
            'closed_at' => 'datetime',
            'comments_locked' => 'boolean',
            'comments_locked_at' => 'datetime',
            'confidence' => 'decimal:2',
            'created_at' => 'datetime',
            'current_stage_due_at' => 'datetime',
            'current_stage_started_at' => 'datetime',
            'customer_form_response' => 'array',
            'customer_form_schema' => 'array',
            'decision' => \App\Enums\IdeaDecision::class,
            'decision_at' => 'datetime',
            'due_at' => 'datetime',
            'effort' => 'decimal:2',
            'escalated_at' => 'datetime',
            'escalation_at' => 'datetime',
            'escalation_l1_at' => 'datetime',
            'escalation_l2_at' => 'datetime',
            'escalation_l3_at' => 'datetime',
            'external_wait_started_at' => 'datetime',
            'first_closed_at' => 'datetime',
            'first_response_at' => 'datetime',
            'idea_stage' => \App\Enums\IdeaStage::class,
            'last_customer_reminder_at' => 'datetime',
            'last_reopened_at' => 'datetime',
            'previous_closed_at' => 'datetime',
            'priority' => \App\Enums\RequestPriority::class,
            'processing_started_at' => 'datetime',
            'published_at' => 'datetime',
            'published_to_customers' => 'boolean',
            'reach' => 'decimal:2',
            'reopen_breached' => 'boolean',
            'reopen_closed_at' => 'datetime',
            'reopen_deadline_at' => 'datetime',
            'reopen_due_at' => 'datetime',
            'reopen_started_at' => 'datetime',
            'response_due_at' => 'datetime',
            'returned_to_customer_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'source_metadata' => 'array',
            'stage_alert_75_sent_at' => 'datetime',
            'stage_alert_90_sent_at' => 'datetime',
            'status' => \App\Enums\RequestStatus::class,
            'tech_bypass_at' => 'datetime',
            'updated_at' => 'datetime',
            'value_score' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(RequestSubCategory::class, 'sub_category_id');
    }

    public function customer()
    {
        return $this->belongsTo(Profile::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function assignee()
    {
        return $this->belongsTo(Profile::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(RequestComment::class);
    }

    public function activities()
    {
        return $this->hasMany(RequestActivityLog::class);
    }

    public function attachments()
    {
        return $this->hasMany(RequestAttachment::class);
    }

    public function ratings()
    {
        return $this->hasMany(RequestRating::class);
    }

    public function tasks()
    {
        return $this->hasMany(RequestTask::class);
    }

    public function stageSlaLogs()
    {
        return $this->hasMany(RequestStageSlaLog::class);
    }
}
