<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Log extends Model
{
    public $timestamps = false;

    protected $casts = [
        'ins_date' => 'datetime',
    ];
    protected $table = 'log';

    protected $fillable = [
        'Content',
    ];

    public function getCreatedAtAttribute(): ?Carbon
    {
        return isset($this->attributes['ins_date']) && $this->attributes['ins_date'] !== null
            ? Carbon::parse($this->attributes['ins_date'])
            : null;
    }
}
