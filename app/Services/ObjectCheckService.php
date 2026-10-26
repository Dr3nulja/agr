<?php

namespace App\Services;

use App\Models\AgrObject;
use App\Services\Legacy\ObjectTablesProvisioner;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Сдача данных по дому. Если в доме и вода, и тепло — два чека по очереди:
 * сначала вода (синий), потом тепло (зелёный = сдано всё). Если в доме
 * что-то одно (или приборов нет) — первый же чек означает «сдано всё».
 */
class ObjectCheckService
{
    public const STEP_WATER = 'water';
    public const STEP_HEAT = 'heat';

    public function __construct(private ReadingsService $readings)
    {
    }

    /**
     * Что сдаётся следующим чеком: 'water' | 'heat' | null (всё уже сдано)
     */
    public function nextStep(AgrObject $object): ?string
    {
        if ($object->selDate) {
            return null;
        }

        $kinds = $this->readings->kinds((int) $object->id);

        if ($kinds[ReadingsService::KIND_WATER] && $kinds[ReadingsService::KIND_HEAT]) {
            return $object->water_checked_at ? self::STEP_HEAT : self::STEP_WATER;
        }

        return $kinds[ReadingsService::KIND_HEAT] ? self::STEP_HEAT : self::STEP_WATER;
    }

    /**
     * Будет ли следующий чек последним (дом станет «сдано всё»)
     */
    public function nextIsFinal(AgrObject $object): bool
    {
        $kinds = $this->readings->kinds((int) $object->id);

        return ! ($kinds[ReadingsService::KIND_WATER] && $kinds[ReadingsService::KIND_HEAT] && ! $object->water_checked_at);
    }

    /**
     * Поставить следующий чек. Возвращает сданный шаг или null, если всё уже сдано.
     */
    public function check(AgrObject $object): ?string
    {
        $step = $this->nextStep($object);

        if ($step === null) {
            return null;
        }

        $final = $this->nextIsFinal($object);
        $now = now();

        if ($step === self::STEP_WATER) {
            $object->water_checked_at = $now;
        } else {
            $object->heat_checked_at = $now;
        }

        if ($final) {
            $this->callLegacyCheck($object);
            $object->selDate = $now;
        }

        $object->save();

        return $step;
    }

    /**
     * Снять оба чека (новый месяц или чек поставлен по ошибке)
     */
    public function reset(AgrObject $object): void
    {
        $object->water_checked_at = null;
        $object->heat_checked_at = null;
        $object->selDate = null;
        $object->save();
    }

    private function callLegacyCheck(AgrObject $object): void
    {
        // Процедура старой системы; в дампе её нет — тогда только отмечаем selDate
        if (app(ObjectTablesProvisioner::class)->procedureExists('CheckObject')) {
            try {
                DB::statement('CALL CheckObject(?)', [$object->id]);
            } catch (QueryException $e) {
                report($e);
            }
        }
    }
}
