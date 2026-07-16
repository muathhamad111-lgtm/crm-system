<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RequestStageChecklistState extends Model
{
    use HasUuids;

    protected $table = 'request_stage_checklist_state';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'checked' => 'boolean',
            'checked_at' => 'datetime',
            'created_at' => 'datetime',
            'required' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
