<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestAttachment extends Model
{
    use HasUuids;

    protected $table = 'request_attachments';

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

    public function comment()
    {
        return $this->belongsTo(RequestComment::class, 'comment_id');
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
