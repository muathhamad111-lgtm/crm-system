<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestStageSlaLog extends Model
{
    use HasUuids;

    protected $table = 'request_stage_sla_log';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assigned_team' => \App\Enums\TargetTeam::class,
            'breached' => 'boolean',
            'created_at' => 'datetime',
            'due_at' => 'datetime',
            'ended_at' => 'datetime',
            'multiplier' => 'decimal:2',
            'paused_at' => 'datetime',
            'priority' => \App\Enums\RequestPriority::class,
            'started_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
