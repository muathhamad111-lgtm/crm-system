<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LeaveType extends Model
{
    use HasUuids;

    protected $table = 'leave_types';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'affects_assignment' => 'boolean',
            'created_at' => 'datetime',
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}
