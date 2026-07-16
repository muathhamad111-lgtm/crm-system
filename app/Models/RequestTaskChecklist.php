<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestTaskChecklist extends Model
{
    use HasUuids;

    protected $table = 'request_task_checklist';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'done_at' => 'datetime',
            'is_done' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    public function task()
    {
        return $this->belongsTo(RequestTask::class, 'task_id');
    }
}
