<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SIM-карта из регистра. Статус меняется только через SimCardService:
 *   free     → issued (выдана технику) | assigned (стоит на объекте)
 *   issued   → free | assigned
 *   assigned → free (на другой объект напрямую — нельзя)
 */
class SimCard extends Model
{
    public const STATUS_FREE = 'free';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_ASSIGNED = 'assigned';

    public const STATUSES = [
        self::STATUS_FREE => 'Vaba',
        self::STATUS_ISSUED => 'Väljastatud',
        self::STATUS_ASSIGNED => 'Määratud',
    ];

    public const SLOTS = [1 => 'SIM 1', 2 => 'SIM 2'];

    // Комментарий, который миграция переноса ставит картам, записанным на нескольких объектах
    public const CONFLICT_NOTE = 'Sama SIM oli kirjas ka objektil';

    protected $table = 'sim_cards';

    protected $fillable = [
        'number',
        'iccid',
        'pin1',
        'pin2',
        'puk1',
        'puk2',
        'comment',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function object(): BelongsTo
    {
        return $this->belongsTo(AgrObject::class, 'object_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    /**
     * Номер для списков: «+37259123456 (8937201…)»
     */
    public function label(): string
    {
        $label = $this->number ?: $this->iccid ?: '#' . $this->id;

        return $this->number && $this->iccid ? $label . ' (' . $this->iccid . ')' : $label;
    }
}
