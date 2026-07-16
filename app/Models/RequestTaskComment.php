<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestTaskComment extends Model
{
    use HasUuids;

    protected $table = 'request_task_comments';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'is_internal' => 'boolean',
        ];
    }

    public function task()
    {
        return $this->belongsTo(RequestTask::class, 'task_id');
    }
}
