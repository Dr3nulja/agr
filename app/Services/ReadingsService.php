<?php

namespace App\Services;

use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Показания водомеров и аллокаторов объекта: история (mlog_N), текущие
 * значения (lastdata_N), потребление по месяцам, ручные правки и фиксация месяца.
 * Значения наружу отдаются уже поделёнными на масштаб прибора (м3, деления).
 */
class ReadingsService
{
    public const KIND_WATER = 'water';
    public const KIND_HEAT = 'heat';

    public const KINDS = [
        self::KIND_WATER => 'Veemõõtjad',
        self::KIND_HEAT => 'Jaoturid',
    ];

    public function __construct(private LegacyObjectsService $legacy)
    {
    }

    public static function isWater(int $devtype): bool
    {
        return $devtype === 1 || $devtype === 2 || ($devtype >= 10 && $devtype < 30);
    }

    public static function isHeat(int $devtype): bool
    {
        return $devtype === 3 || ($devtype >= 30 && $devtype <= 39);
    }

    public static function kindOf(int $devtype): ?string
    {
        return match (true) {
            self::isWater($devtype) => self::KIND_WATER,
            self::isHeat($devtype) => self::KIND_HEAT,
            default => null,
        };
    }

    public static function typeLabel(int $devtype): string
    {
        return match (true) {
            $devtype === 1, $devtype >= 10 && $devtype < 20 => 'külm vesi',
            $devtype === 2, $devtype >= 20 && $devtype < 30 => 'soe vesi',
            self::isHeat($devtype) => 'jaotur',
            default => 'tüüp ' . $devtype,
        };
    }

    public function hasTables(int $objectId): bool
    {
        return Schema::hasTable('object_' . $objectId)
            && Schema::hasTable('lastdata_' . $objectId)
            && Schema::hasTable('mlog_' . $objectId);
    }

    /**
     * Какие приборы есть в доме: ['water' => bool, 'heat' => bool]
     */
    public function kinds(int $objectId): array
    {
        $kinds = [self::KIND_WATER => false, self::KIND_HEAT => false];

        if (! Schema::hasTable('object_' . $objectId)) {
            return $kinds;
        }

        foreach (DB::table('object_' . $objectId)->distinct()->pluck('devtype') as $devtype) {
            if ($kind = self::kindOf((int) $devtype)) {
                $kinds[$kind] = true;
            }
        }

        return $kinds;
    }

    /**
     * Водомеры и аллокаторы с текущими значениями.
     * $kind — 'water' | 'heat' | null (оба), $location — квартира или ''.
     */
    public function devices(int $objectId, ?string $kind = null, string $location = ''): array
    {
        if (! $this->hasTables($objectId)) {
            return [];
        }

        return DB::table('object_' . $objectId . ' as O')
            ->leftJoin('lastdata_' . $objectId . ' as L', 'O.devid', '=', 'L.devid')
            ->when($location !== '', fn ($query) => $query->where('O.location', $location))
            ->orderBy('O.id')
            ->get(['O.devid', 'O.location', 'O.devtype', 'L.date', 'L.value', 'L.mvalue', 'L.prevVal'])
            ->map(function ($row) {
                $devtype = (int) $row->devtype;
                $scale = $this->legacy->deviceScale($devtype);

                return [
                    'devid' => (int) $row->devid,
                    'location' => (string) $row->location,
                    'devtype' => $devtype,
                    'kind' => self::kindOf($devtype),
                    'type_label' => self::typeLabel($devtype),
                    'scale' => $scale,
                    'date' => $row->date,
                    'value' => $row->value !== null ? $row->value / $scale : null,
                    'mvalue' => $row->mvalue !== null ? $row->mvalue / $scale : null,
                    'prev_value' => $row->prevVal !== null ? $row->prevVal / $scale : null,
                ];
            })
            ->filter(fn ($device) => $device['kind'] !== null && ($kind === null || $device['kind'] === $kind))
            ->sortBy(fn ($device) => sprintf('%08d%s|%010d', (int) $device['location'], $device['location'], $device['devid']))
            ->values()
            ->all();
    }

    public function locations(int $objectId): array
    {
        if (! Schema::hasTable('object_' . $objectId)) {
            return [];
        }

        return DB::table('object_' . $objectId)
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->pluck('location')
            ->sortBy(fn ($location) => sprintf('%08d%s', (int) $location, $location))
            ->values()
            ->all();
    }

    /**
     * Месяцы периода: ['2026-01', '2026-02', ...]
     */
    public static function periods(string $from, string $to): array
    {
        $periods = [];
        $month = Carbon::createFromFormat('Y-m-d', $from . '-01')->startOfMonth();
        $last = Carbon::createFromFormat('Y-m-d', $to . '-01')->startOfMonth();

        while ($month->lte($last) && count($periods) < 120) {
            $periods[] = $month->format('Y-m');
            $month->addMonth();
        }

        return $periods;
    }

    /**
     * Показание на конец каждого месяца (последняя запись mlog_N в месяце)
     * и потребление за месяц = показание месяца − показание прошлого месяца.
     * Если счётчик начался заново (аллокаторы в начале сезона), потребление = показание.
     *
     * @return array devid => [period => ['reading' => float, 'date' => string, 'consumption' => ?float]]
     */
    public function monthly(int $objectId, array $devices, string $from, string $to): array
    {
        if ($devices === [] || ! $this->hasTables($objectId)) {
            return [];
        }

        $table = 'mlog_' . $objectId;
        $start = Carbon::createFromFormat('Y-m-d', $from . '-01')->startOfMonth()->subMonth();
        $end = Carbon::createFromFormat('Y-m-d', $to . '-01')->startOfMonth()->addMonth();
        $scales = collect($devices)->pluck('scale', 'devid');

        $lastPerMonth = DB::table($table)
            ->select('devid', DB::raw('MAX(date) as max_date'))
            ->whereIn('devid', $scales->keys())
            ->where('date', '>=', $start)
            ->where('date', '<', $end)
            ->groupBy('devid', DB::raw("DATE_FORMAT(date, '%Y-%m')"));

        $rows = DB::table($table . ' as M')
            ->joinSub($lastPerMonth, 'LM', function ($join) {
                $join->on('LM.devid', '=', 'M.devid')->on('LM.max_date', '=', 'M.date');
            })
            ->orderBy('M.date')
            ->get(['M.devid', 'M.date', 'M.value']);

        $readings = [];
        foreach ($rows as $row) {
            $readings[(int) $row->devid][substr($row->date, 0, 7)] = [
                'reading' => $row->value / ($scales[(int) $row->devid] ?? 1),
                'date' => $row->date,
            ];
        }

        $periods = self::periods($from, $to);
        $result = [];

        foreach ($readings as $devid => $months) {
            foreach ($periods as $period) {
                if (! isset($months[$period])) {
                    continue;
                }
                $previous = $months[Carbon::createFromFormat('Y-m-d', $period . '-01')->subMonth()->format('Y-m')] ?? null;
                $reading = $months[$period]['reading'];
                $consumption = null;
                if ($previous !== null) {
                    $diff = $reading - $previous['reading'];
                    $consumption = $diff >= 0 ? $diff : $reading;
                }
                $result[$devid][$period] = $months[$period] + ['consumption' => $consumption];
            }
        }

        return $result;
    }

    /**
     * Потребление по месяцам суммарно для графика: kind => [period => float]
     */
    public static function totals(array $devices, array $monthly, array $periods): array
    {
        $totals = [];

        foreach ($devices as $device) {
            foreach ($periods as $period) {
                $totals[$device['kind']][$period] ??= 0.0;
                $totals[$device['kind']][$period] += $monthly[$device['devid']][$period]['consumption'] ?? 0;
            }
        }

        return $totals;
    }

    public function device(int $objectId, int $devid): ?array
    {
        return collect($this->devices($objectId))->firstWhere('devid', $devid);
    }

    /**
     * История прибора (mlog_N), новые сверху
     */
    public function history(int $objectId, array $device, int $limit = 200): array
    {
        $corrected = DB::table('reading_corrections')
            ->where('object_id', $objectId)
            ->where('devid', $device['devid'])
            ->whereIn('action', ['history_add', 'history_edit'])
            ->get(['new_values'])
            ->map(fn ($row) => json_decode($row->new_values, true)['id'] ?? null)
            ->filter()
            ->flip();

        return DB::table('mlog_' . $objectId)
            ->where('devid', $device['devid'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'date', 'value'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'date' => $row->date,
                'value' => $row->value / $device['scale'],
                'corrected' => isset($corrected[(int) $row->id]),
            ])
            ->all();
    }

    public function corrections(int $objectId, int $devid, int $limit = 20)
    {
        return DB::table('reading_corrections')
            ->where('object_id', $objectId)
            ->where('devid', $devid)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Добавить показание в историю вручную (например, прибор не передал данные).
     * Если оно новее текущего — обновляем и текущее значение прибора.
     */
    public function addHistory(int $objectId, array $device, string $date, float $value, string $user): void
    {
        DB::transaction(function () use ($objectId, $device, $date, $value, $user) {
            $raw = $this->toRaw($value, $device);
            $id = DB::table('mlog_' . $objectId)->insertGetId([
                'devid' => $device['devid'],
                'date' => $date,
                'value' => $raw,
            ]);

            $this->log($objectId, $device['devid'], 'history_add', null, ['id' => $id, 'date' => $date, 'value' => $raw], $user);
            $this->refreshCurrentFromHistory($objectId, $device, $user);
        });
    }

    public function updateHistory(int $objectId, array $device, int $rowId, string $date, float $value, string $user): void
    {
        DB::transaction(function () use ($objectId, $device, $rowId, $date, $value, $user) {
            $row = $this->historyRow($objectId, $device, $rowId);
            $raw = $this->toRaw($value, $device);

            DB::table('mlog_' . $objectId)->where('id', $rowId)->update(['date' => $date, 'value' => $raw]);

            $this->log($objectId, $device['devid'], 'history_edit',
                ['id' => $rowId, 'date' => $row->date, 'value' => $row->value],
                ['id' => $rowId, 'date' => $date, 'value' => $raw], $user);
            $this->refreshCurrentFromHistory($objectId, $device, $user);
        });
    }

    public function deleteHistory(int $objectId, array $device, int $rowId, string $user): void
    {
        DB::transaction(function () use ($objectId, $device, $rowId, $user) {
            $row = $this->historyRow($objectId, $device, $rowId);

            DB::table('mlog_' . $objectId)->where('id', $rowId)->delete();

            $this->log($objectId, $device['devid'], 'history_delete', ['id' => $rowId, 'date' => $row->date, 'value' => $row->value], null, $user);
        });
    }

    /**
     * Правка текущих значений (lastdata_N), по которым считают потребление в отчётах:
     * дата и показание, месячное показание (mvalue) и предыдущее (prevVal).
     */
    public function updateCurrent(int $objectId, array $device, array $values, string $user): void
    {
        $table = 'lastdata_' . $objectId;

        DB::transaction(function () use ($table, $objectId, $device, $values, $user) {
            $old = DB::table($table)->where('devid', $device['devid'])->first();
            $new = [
                'date' => $values['date'],
                'value' => $this->toRaw($values['value'], $device),
                'mvalue' => $this->toRaw($values['mvalue'], $device),
                'prevVal' => $this->toRaw($values['prev_value'], $device),
            ];

            if ($old === null) {
                DB::table($table)->insert(['devid' => $device['devid']] + $new);
            } else {
                // Триггер ins_data_N при смене mvalue перезаписывает prevVal старым mvalue,
                // поэтому prevVal ставим отдельным запросом после mvalue
                DB::table($table)->where('devid', $device['devid'])->update(collect($new)->except('prevVal')->all());
                DB::table($table)->where('devid', $device['devid'])->update(['prevVal' => $new['prevVal']]);
            }

            $this->log($objectId, $device['devid'], 'current_edit',
                $old ? ['date' => $old->date, 'value' => $old->value, 'mvalue' => $old->mvalue, 'prevVal' => $old->prevVal] : null,
                $new, $user);
        });
    }

    /**
     * Зафиксировать показания месяца: снимок текущих значений всех приборов дома.
     * Повторная фиксация того же месяца перезаписывает снимок.
     */
    public function fixMonth(int $objectId, string $period, string $user): int
    {
        $devices = $this->devices($objectId);
        $now = now();

        DB::transaction(function () use ($objectId, $period, $user, $devices, $now) {
            DB::table('monthly_readings')->where('object_id', $objectId)->where('period', $period)->delete();

            foreach (array_chunk($devices, 500) as $chunk) {
                DB::table('monthly_readings')->insert(array_map(fn ($device) => [
                    'object_id' => $objectId,
                    'period' => $period,
                    'devid' => $device['devid'],
                    'location' => $device['location'],
                    'devtype' => $device['devtype'],
                    'reading_date' => $device['date'],
                    'value' => $this->toRaw($device['value'], $device),
                    'mvalue' => $this->toRaw($device['mvalue'], $device),
                    'prev_value' => $this->toRaw($device['prev_value'], $device),
                    'fixed_by' => $user,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }
        });

        return count($devices);
    }

    /**
     * Зафиксированные месяцы: [['period' => '2026-09', 'count' => 120, 'fixed_at' => ..., 'fixed_by' => ...]]
     */
    public function fixedPeriods(int $objectId): array
    {
        return DB::table('monthly_readings')
            ->where('object_id', $objectId)
            ->groupBy('period')
            ->orderByDesc('period')
            ->get(['period', DB::raw('COUNT(*) as count'), DB::raw('MAX(created_at) as fixed_at'), DB::raw('MAX(fixed_by) as fixed_by')])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * Снимок месяца в том же виде, что devices()
     */
    public function fixedDevices(int $objectId, string $period, ?string $kind = null, string $location = ''): array
    {
        return DB::table('monthly_readings')
            ->where('object_id', $objectId)
            ->where('period', $period)
            ->when($location !== '', fn ($query) => $query->where('location', $location))
            ->get()
            ->map(function ($row) {
                $devtype = (int) $row->devtype;
                $scale = $this->legacy->deviceScale($devtype);

                return [
                    'devid' => (int) $row->devid,
                    'location' => (string) $row->location,
                    'devtype' => $devtype,
                    'kind' => self::kindOf($devtype),
                    'type_label' => self::typeLabel($devtype),
                    'scale' => $scale,
                    'date' => $row->reading_date,
                    'value' => $row->value !== null ? $row->value / $scale : null,
                    'mvalue' => $row->mvalue !== null ? $row->mvalue / $scale : null,
                    'prev_value' => $row->prev_value !== null ? $row->prev_value / $scale : null,
                ];
            })
            ->filter(fn ($device) => $kind === null || $device['kind'] === $kind)
            ->sortBy(fn ($device) => sprintf('%08d%s|%010d', (int) $device['location'], $device['location'], $device['devid']))
            ->values()
            ->all();
    }

    private function historyRow(int $objectId, array $device, int $rowId): object
    {
        $row = DB::table('mlog_' . $objectId)->where('id', $rowId)->where('devid', $device['devid'])->first();

        if ($row === null) {
            throw ValidationException::withMessages(['reading' => 'Näitu ei leitud']);
        }

        return $row;
    }

    /**
     * Текущее значение прибора = самая новая запись истории (как при импорте)
     */
    private function refreshCurrentFromHistory(int $objectId, array $device, string $user): void
    {
        $latest = DB::table('mlog_' . $objectId)->where('devid', $device['devid'])->orderByDesc('date')->orderByDesc('id')->first();
        $current = DB::table('lastdata_' . $objectId)->where('devid', $device['devid'])->first();

        if ($latest === null || ($current !== null && strtotime($current->date) > strtotime($latest->date))) {
            return;
        }

        if ($current !== null && $current->date === $latest->date && (int) $current->value === (int) $latest->value) {
            return;
        }

        DB::table('lastdata_' . $objectId)->updateOrInsert(
            ['devid' => $device['devid']],
            ['date' => $latest->date, 'value' => $latest->value, 'inserterd' => now()]
        );

        $this->log($objectId, $device['devid'], 'current_edit',
            $current ? ['date' => $current->date, 'value' => $current->value] : null,
            ['date' => $latest->date, 'value' => $latest->value], $user);
    }

    private function toRaw(?float $value, array $device): ?int
    {
        return $value === null ? null : (int) round($value * $device['scale']);
    }

    private function log(int $objectId, int $devid, string $action, ?array $old, ?array $new, string $user): void
    {
        DB::table('reading_corrections')->insert([
            'object_id' => $objectId,
            'devid' => $devid,
            'action' => $action,
            'old_values' => $old !== null ? json_encode($old) : null,
            'new_values' => $new !== null ? json_encode($new) : null,
            'user' => $user,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
