<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Profile extends Model
{
    use HasUuids;

    protected $table = 'profiles';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'last_contact_at' => 'datetime',
            'suspended' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function roles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function assignedRequests()
    {
        return $this->hasMany(Request::class, 'assigned_to');
    }
}
