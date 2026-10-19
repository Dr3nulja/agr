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
        'installer',
        'client_uuid',
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
     * Поля формы приложения: ключ => [подпись, для какого прибора].
     * null — для любого. Приложение шлёт поля водомера и для распределителя
     * (dn_type=15, water_type=1 по умолчанию), поэтому чужие поля скрываем.
     */
    public const FIELDS = [
        'device_type' => ['Seadme tüüp', null],
        'action_type' => ['Toiming', null],
        'apartment' => ['Korter', null],
        'qr_id' => ['QR-kood', null],
        'last_reading' => ['Viimane näit enne vahetust', 'water_meter'],
        'dn_type' => ['Toru tüüp', 'water_meter'],
        'water_type' => ['Vee tüüp', 'water_meter'],
        'size' => ['Suurus', 'water_meter'],
        'kitchen_floor' => ['Köök: põrand', 'water_meter'],
        'kitchen_ceiling' => ['Köök: lagi', 'water_meter'],
        'kitchen_comment' => ['Köögi märkus', 'water_meter'],
        'bathroom_floor' => ['Vannituba: põrand', 'water_meter'],
        'bathroom_ceiling' => ['Vannituba: lagi', 'water_meter'],
        'bathroom_comment' => ['Vannitoa märkus', 'water_meter'],
        'allocator_location' => ['Jaoturi asukoht', 'allocator'],
        'allocator_location_comment' => ['Asukoha täpsustus', 'allocator'],
        'radiator_type' => ['Radiaatori tüüp', 'allocator'],
        'radiator_panel_type' => ['Radiaatori paneel', 'allocator'],
        'radiator_width' => ['Radiaatori laius', 'allocator'],
        'radiator_depth' => ['Radiaatori sügavus', 'allocator'],
        'radiator_height' => ['Radiaatori kõrgus', 'allocator'],
        'radiator_length' => ['Radiaatori pikkus', 'allocator'],
        'allocator_comment' => ['Jaoturi märkus', 'allocator'],
        'photo_before_comment' => ['Märkus enne pildi kohta', null],
        'photo_after_comment' => ['Märkus pärast pildi kohta', null],
    ];

    /**
     * Заполненные поля формы с подписями, в порядке формы приложения:
     * ключ => [подпись, значение]. Комментарии к полям («Lisa kommentaar»)
     * идут как «Kommentaar: <поле>». Неизвестные новые поля — в конце как есть.
     */
    public function fields(): array
    {
        $data = ($this->data ?? []) + ['last_reading' => $this->last_reading];
        $fields = [];

        foreach (self::FIELDS as $key => [$label, $device]) {
            if (! in_array($key, ['device_type', 'action_type', 'apartment', 'qr_id'], true) && $this->fieldApplies($device)) {
                $this->addField($fields, $key, $label, $data[$key] ?? null);
            }

            $commentKey = $key . '_comment';
            if (! isset(self::FIELDS[$commentKey]) && $this->fieldApplies($device)) {
                $this->addField($fields, $commentKey, 'Kommentaar: ' . mb_strtolower($label), $data[$commentKey] ?? null);
            }
        }

        foreach ($data as $key => $value) {
            if (! isset($fields[$key]) && ! isset(self::FIELDS[$key]) && ! isset(self::FIELDS[preg_replace('/_comment$/', '', $key)])) {
                $this->addField($fields, $key, $key, $value);
            }
        }

        return $fields;
    }

    private function fieldApplies(?string $device): bool
    {
        return $device === null || $device === $this->device_type;
    }

    private function addField(array &$fields, string $key, string $label, $value): void
    {
        $value = self::formatValue($key, $value);

        if ($value !== null) {
            $fields[$key] = [$label, $value];
        }
    }

    private static function formatValue(string $key, $value): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (in_array($key, ['kitchen_floor', 'kitchen_ceiling', 'bathroom_floor', 'bathroom_ceiling'], true)) {
            return $value === '1' ? 'Jah' : null;
        }

        return match (true) {
            $value === '' => null,
            $key === 'water_type' => ['1' => 'Külm vesi', '2' => 'Soe vesi'][$value] ?? $value,
            $key === 'dn_type' => 'DN ' . $value,
            default => $value,
        };
    }

    /**
     * Ключ для сортировки квартир по-человечески: 2 < 10 < 10a
     */
    public static function apartmentSortKey(?string $apartment): string
    {
        $apartment = trim((string) $apartment);

        return preg_match('/^(\d+)(.*)$/', $apartment, $m)
            ? sprintf('%08d%s', $m[1], mb_strtolower($m[2]))
            : 'zzzzzzzz' . mb_strtolower($apartment);
    }

    /**
     * Квартира для сравнения со списком квартир объекта (flat_N.location)
     */
    public static function normalizeApartment(?string $apartment): string
    {
        return mb_strtolower(preg_replace('/\s+/', '', (string) $apartment));
    }
}
