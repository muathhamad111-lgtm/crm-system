<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserRole extends Model
{
    use HasUuids;

    protected $table = 'user_roles';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'role' => \App\Enums\AppRole::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
