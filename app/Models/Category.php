<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Category extends Model
{
    use HasUuids;

    protected $table = 'categories';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'allowed_channels' => 'array',
            'approval_steps' => 'array',
            'automation_rules' => 'array',
            'created_at' => 'datetime',
            'default_priority' => \App\Enums\RequestPriority::class,
            'escalation_channels' => 'array',
            'escalation_l1_role' => \App\Enums\AppRole::class,
            'escalation_l2_role' => \App\Enums\AppRole::class,
            'escalation_l3_role' => \App\Enums\AppRole::class,
            'escalation_l4_role' => \App\Enums\AppRole::class,
            'escalation_thresholds' => 'array',
            'escalation_to_role' => \App\Enums\AppRole::class,
            'freeze_on_holidays' => 'boolean',
            'is_suggestion' => 'boolean',
            'negative_alert_recipients' => 'array',
            'open_question_enabled' => 'boolean',
            'rating_base_config' => 'array',
            'rating_channels' => 'array',
            'rating_enabled' => 'boolean',
            'rating_questions' => 'array',
            'required_fields_per_state' => 'array',
            'routing_rules' => 'array',
            'target_team' => \App\Enums\TargetTeam::class,
            'transitions_matrix' => 'array',
            'updated_at' => 'datetime',
            'workflow_steps' => 'array',
        ];
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function subCategories()
    {
        return $this->hasMany(RequestSubCategory::class, 'category_id');
    }
}
