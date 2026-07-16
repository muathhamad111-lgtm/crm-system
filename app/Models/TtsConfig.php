<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TtsConfig extends Model
{
    protected $table = 'tts_config';

    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }
}
