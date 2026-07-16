<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AssignmentAuditLog extends Model
{
    use HasUuids;

    protected $table = 'assignment_audit_log';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'unavailability_reasons' => 'array',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
