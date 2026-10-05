<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Отчёт монтажника из мобильного приложения (одна квартира / один прибор)
 */
class MobileReport extends Model
{
    public const FILES = ['photo_before', 'photo_after', 'signature'];

    protected $table = 'mobile_reports';

    protected $fillable = [
        'object_id',
        'device_type',
        'action_type',
        'apartment',
        'qr_id',
        'last_reading',
        'data',
        'photo_before',
        'photo_after',
        'signature',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function object(): BelongsTo
    {
        return $this->belongsTo(AgrObject::class, 'object_id');
    }

    public function taskLabel(): string
    {
        return sprintf(
            '%s — %s',
            ObjectTask::DEVICE_TYPES[$this->device_type] ?? $this->device_type,
            ObjectTask::ACTION_TYPES[$this->action_type] ?? $this->action_type
        );
    }

    /**
     * Непустые поля формы для показа в дашборде
     */
    public function filledData(): array
    {
        return array_filter($this->data ?? [], fn ($value) => $value !== null && $value !== '' && $value !== '0');
    }
}
