<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Announcement extends Model
{
    use HasUuids;

    protected $table = 'announcements';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'published_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
