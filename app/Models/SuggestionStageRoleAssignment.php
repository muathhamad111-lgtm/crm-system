<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SuggestionStageRoleAssignment extends Model
{
    use HasUuids;

    protected $table = 'suggestion_stage_role_assignments';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'role' => \App\Enums\AppRole::class,
            'updated_at' => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
