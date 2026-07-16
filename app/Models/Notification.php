<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends Model
{
    use HasUuids;

    protected $table = 'notifications';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function ttsCustomer()
    {
        return $this->belongsTo(TtsCustomer::class, 'tts_customer_id');
    }
}
