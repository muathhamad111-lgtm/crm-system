<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestNumberSeq extends Model
{
    protected $table = 'request_number_seqs';

    protected $primaryKey = 'category_id';
    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
