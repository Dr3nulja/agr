<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Csq extends Model
{
    protected $table = 'csq';

    protected $fillable = [
        'object',
        'csq',
    ];

    public function object(): BelongsTo
    {
        return $this->belongsTo(AgrObject::class, 'object');
    }
}
