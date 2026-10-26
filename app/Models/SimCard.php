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
     * Ключ для сравнения номеров: +37259123456, 0037259123456, 37259123456 и 59123456 — один номер
     */
    public static function numberKey(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);
        $digits = preg_replace('/^00/', '', $digits);

        return strlen($digits) > 8 && str_starts_with($digits, '372') ? substr($digits, 3) : $digits;
    }

    /**
     * Карта с тем же номером в другом написании (кроме $exceptId)
     */
    public static function findByNumber(string $number, ?int $exceptId = null): ?self
    {
        $key = self::numberKey($number);

        if ($key === '') {
            return null;
        }

        return self::query()
            ->where('number', 'like', '%' . $key)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->get()
            ->first(fn (self $sim) => self::numberKey($sim->number) === $key);
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
