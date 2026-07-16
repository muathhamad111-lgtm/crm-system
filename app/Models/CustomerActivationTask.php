<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CustomerActivationTask extends Model
{
    use HasUuids;

    protected $table = 'customer_activation_tasks';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'due_date' => 'date',
            'updated_at' => 'datetime',
        ];
    }

    public function assignee()
    {
        return $this->belongsTo(Profile::class, 'assigned_to');
    }

    public function customer()
    {
        return $this->belongsTo(Profile::class, 'customer_id');
    }
}
