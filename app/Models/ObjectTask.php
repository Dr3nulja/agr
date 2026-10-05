<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Задача на объекте для мобильного приложения: тип прибора + действие.
 * Значения совпадают с device_type / action_type, которые приложение отправляет в insert_dev_data.
 */
class ObjectTask extends Model
{
    public const DEVICE_TYPES = [
        'water_meter' => 'Veearvesti',
        'allocator' => 'Soojusjaotur',
    ];

    public const ACTION_TYPES = [
        'replace' => 'Vahetus',
        'install' => 'Paigaldus',
    ];

    protected $table = 'object_tasks';

    protected $fillable = [
        'object_id',
        'device_type',
        'action_type',
    ];

    public function object(): BelongsTo
    {
        return $this->belongsTo(AgrObject::class, 'object_id');
    }

    public function label(): string
    {
        return sprintf(
            '%s — %s',
            self::DEVICE_TYPES[$this->device_type] ?? $this->device_type,
            self::ACTION_TYPES[$this->action_type] ?? $this->action_type
        );
    }
}
