<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $table = 'api_request_log';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }
}
