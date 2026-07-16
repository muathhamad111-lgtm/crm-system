<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestTaskAttachment extends Model
{
    use HasUuids;

    protected $table = 'request_task_attachments';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function task()
    {
        return $this->belongsTo(RequestTask::class, 'task_id');
    }
}
