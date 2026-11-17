<?php

namespace App\Services;

use App\Models\AgrObject;
use App\Models\LeakAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Поиск утечек воды: суточный расход водомера по истории mlog_N.
 *
 * Расход за день D = последнее показание за D − последнее показание до D
 * (ищем не дальше 7 дней назад). Если между ними больше суток (прибор
 * пропускал связь), расход делится на число суток — сравнивается средний
 * суточный расход. Если показаний до D нет — берётся первое показание за D.
 * Показание уменьшилось (замена / сброс прибора) — не утечка.
 */
class LeakDetector
{
    private const LOOKBACK_DAYS = 7;

    public function __construct(private ReadingsService $readings)
    {
    }

    public static function threshold(): float
    {
        return (float) config('agr.leak_daily_m3', 5);
    }

    /**
     * Проверить один день по всем объектам (или одному). Возвращает утечки,
     * найденные впервые (уже записанные раньше повторно не возвращаются).
     *
     * @return LeakAlert[]
     */
    public function scanDay(Carbon $day, ?int $objectId = null): array
    {
        $new = [];

        AgrObject::query()
            ->when($objectId !== null, fn ($query) => $query->whereKey($objectId))
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) use ($day, &$new) {
                foreach ($this->scanObjectDay((int) $id, $day) as $alert) {
                    if ($alert->wasRecentlyCreated) {
                        $new[] = $alert;
                    }
                }
            });

        return $new;
    }

    /**
     * Утечки одного дома за день D (записываются в leak_alerts)
     *
     * @return LeakAlert[]
     */
    public function scanObjectDay(int $objectId, Carbon $day): array
    {
        $alerts = [];

        foreach ($this->dailyConsumption($objectId, $day) as $row) {
            if ($row['per_day'] < self::threshold()) {
                continue;
            }

            $alert = LeakAlert::firstOrNew(['object_id' => $objectId, 'devid' => $row['devid'], 'day' => $day->toDateString()]);
            $alert->fill([
                'location' => $row['location'],
                'devtype' => $row['devtype'],
                'consumption' => round($row['per_day'], 3),
                'from_value' => $row['from_value'],
                'to_value' => $row['to_value'],
                'from_date' => $row['from_date'],
                'to_date' => $row['to_date'],
            ])->save();

            $alerts[] = $alert;
        }

        return $alerts;
    }

    /**
     * Суточный расход каждого водомера дома за день D
     *
     * @return array<int, array{devid:int, location:string, devtype:int, per_day:float, from_value:float, to_value:float, from_date:string, to_date:string}>
     */
    public function dailyConsumption(int $objectId, Carbon $day): array
    {
        if (! $this->readings->hasTables($objectId)) {
            return [];
        }

        $devices = collect($this->readings->devices($objectId, ReadingsService::KIND_WATER))->keyBy('devid');
        if ($devices->isEmpty()) {
            return [];
        }

        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();
        $table = 'mlog_' . $objectId;
        $devids = $devices->keys()->all();

        $end = $this->pointPerDevice($table, $devids, $dayStart, $dayEnd, 'MAX');
        $before = $this->pointPerDevice($table, $devids, $dayStart->copy()->subDays(self::LOOKBACK_DAYS), $dayStart, 'MAX');
        $firstOfDay = $this->pointPerDevice($table, $devids, $dayStart, $dayEnd, 'MIN');

        $result = [];
        foreach ($end as $devid => $last) {
            $start = $before[$devid] ?? $firstOfDay[$devid] ?? null;
            if ($start === null || $start['date'] >= $last['date']) {
                continue;
            }

            $scale = $devices[$devid]['scale'] ?: 1;
            $diff = ($last['value'] - $start['value']) / $scale;
            if ($diff < 0) {
                continue;
            }

            $days = (strtotime($last['date']) - strtotime($start['date'])) / 86400;

            $result[] = [
                'devid' => $devid,
                'location' => $devices[$devid]['location'],
                'devtype' => $devices[$devid]['devtype'],
                'per_day' => $diff / max($days, 1),
                'from_value' => $start['value'] / $scale,
                'to_value' => $last['value'] / $scale,
                'from_date' => $start['date'],
                'to_date' => $last['date'],
            ];
        }

        return $result;
    }

    /**
     * Первое (MIN) или последнее (MAX) показание каждого прибора в интервале [from, to)
     *
     * @return array<int, array{date:string, value:float}>
     */
    private function pointPerDevice(string $table, array $devids, Carbon $from, Carbon $to, string $aggregate): array
    {
        $edge = DB::table($table)
            ->select('devid', DB::raw($aggregate . '(date) as edge_date'))
            ->whereIn('devid', $devids)
            ->where('date', '>=', $from->format('Y-m-d H:i:s'))
            ->where('date', '<', $to->format('Y-m-d H:i:s'))
            ->groupBy('devid');

        return DB::table($table . ' as M')
            ->joinSub($edge, 'E', function ($join) {
                $join->on('E.devid', '=', 'M.devid')->on('E.edge_date', '=', 'M.date');
            })
            ->orderBy('M.id')
            ->get(['M.devid', 'M.date', 'M.value'])
            ->mapWithKeys(fn ($row) => [(int) $row->devid => ['date' => (string) $row->date, 'value' => (float) $row->value]])
            ->all();
    }
}
