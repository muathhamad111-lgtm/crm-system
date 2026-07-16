<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PrioritySla extends Model
{
    use HasUuids;

    protected $table = 'priority_sla';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'priority' => \App\Enums\RequestPriority::class,
            'updated_at' => 'datetime',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
