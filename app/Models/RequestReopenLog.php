<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestReopenLog extends Model
{
    use HasUuids;

    protected $table = 'request_reopen_log';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'created_at' => 'datetime',
            'escalation_triggered' => 'boolean',
            'previous_closed_at' => 'datetime',
            'sla_breached' => 'boolean',
            'sla_closed_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'sla_started_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
