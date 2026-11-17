<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Утечка: водомер насчитал за сутки ≥ config('agr.leak_daily_m3') м³
 */
class LeakAlert extends Model
{
    protected $table = 'leak_alerts';

    protected $fillable = [
        'object_id',
        'devid',
        'location',
        'devtype',
        'day',
        'consumption',
        'from_value',
        'to_value',
        'from_date',
        'to_date',
        'notified_at',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'day' => 'date',
        'consumption' => 'float',
        'from_value' => 'float',
        'to_value' => 'float',
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function object(): BelongsTo
    {
        return $this->belongsTo(AgrObject::class, 'object_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
