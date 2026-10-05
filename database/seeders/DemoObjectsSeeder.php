<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo objects for local development, used instead of restoring the production dump.
 *
 * Besides the `objects` rows it builds the legacy per-object tables that production
 * gets from the NewObject stored procedure (object_N, lastdata_N, mlog_N, flat_N,
 * heat_N) and fills them with plausible readings, so the object page, the error
 * counters, the XLSX/CSV exports and the SOE allocation all have something to show.
 *
 * Re-runnable: it wipes objects and their per-object tables before seeding.
 *
 *   php artisan db:seed --class=DemoObjectsSeeder
 */
class DemoObjectsSeeder extends Seeder
{
    private const HISTORY_DAYS = 100;

    // dtype => label, as shown in the object list
    private const SYSTEMS = [
        1 => 'Apator',
        2 => 'Siemens',
        3 => 'Elekter',
        4 => 'LoRa',
    ];

    private const BUILDINGS = [
        ['Sõpruse pst 201', 'Tallinn', 59.4105, 24.6853],
        ['Mustamäe tee 12', 'Tallinn', 59.4241, 24.7012],
        ['Pärnu mnt 145', 'Tallinn', 59.4113, 24.7298],
        ['Tartu mnt 52', 'Tallinn', 59.4290, 24.7713],
        ['Akadeemia tee 36', 'Tallinn', 59.3989, 24.6621],
        ['Ehitajate tee 108', 'Tallinn', 59.3952, 24.6716],
        ['Lasnamäe 18', 'Tallinn', 59.4318, 24.8152],
        ['Kalda 9', 'Tallinn', 59.4072, 24.7604],
        ['Paldiski mnt 64', 'Tallinn', 59.4329, 24.6915],
        ['Sütiste tee 30', 'Tallinn', 59.3995, 24.6807],
        ['Punane 41', 'Tallinn', 59.4365, 24.8261],
        ['Läänemere tee 22', 'Tallinn', 59.4441, 24.8470],
        ['Riia 130', 'Tartu', 58.3612, 26.6987],
        ['Anne 57', 'Tartu', 58.3729, 26.7454],
        ['Kaunase pst 21', 'Tartu', 58.3732, 26.7663],
        ['Raatuse 88', 'Tartu', 58.3843, 26.7368],
        ['Papiniidu 40', 'Pärnu', 58.3713, 24.5401],
        ['Tammsaare pst 15', 'Pärnu', 58.3779, 24.5352],
        ['Tallinna mnt 21', 'Narva', 59.3792, 28.1903],
        ['Kreenholmi 8', 'Narva', 59.3699, 28.1915],
        ['Rakvere 44', 'Jõhvi', 59.3571, 27.4180],
        ['Kesk 6', 'Viljandi', 58.3640, 25.5970],
        ['Jaama 12', 'Haapsalu', 58.9395, 23.5422],
        ['Rüütli 3', 'Kuressaare', 58.2511, 22.4846],
    ];

    private const CONTACTS = [
        'KÜ Sõpruse 201, esimees Mart Tamm',
        'Jelena Ivanova, +372 5551 2345',
        'Hooldus OÜ, Andres Kask',
        'KÜ juhatus, info@ku-demo.ee',
        'Sergei Volkov, +372 5341 9876',
        'Kadri Saar, haldur',
        'Pavel Smirnov, +372 5212 4433',
        'Linnahaldus AS, dispetšer',
    ];

    private const ERROR_CODES = [1, 2, 4, 8, 16];

    private int $nextDevid = 60100000;

    public function run(): void
    {
        mt_srand(20241005);
        $now = Carbon::now();

        $this->wipe();
        $this->createProvisioningProcedures();

        foreach (self::BUILDINGS as $index => [$address, $city, $lat, $lon]) {
            $dtype = [1, 2, 1, 4, 2, 1, 3, 2, 1, 4, 2, 1, 2, 1, 4, 2, 1, 3, 2, 1, 4, 1, 2, 1][$index];
            $flatCount = mt_rand(8, 36);
            $objectId = $this->insertObject($index, $address, $city, $lat, $lon, $dtype, $flatCount, $now);

            $this->createObjectTables($objectId, $dtype === 3);
            $this->fillObjectTables($objectId, $dtype, $flatCount, $now);
            $this->insertInstallData($objectId);
            $this->insertCsqHistory($objectId, $now);
        }

        $this->insertRequests($now);
        $this->insertLog($now);
    }

    private function wipe(): void
    {
        $perObjectTables = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'regexp', '^(object|lastdata|mlog|flat|heat)_[0-9]+$')
            ->pluck('TABLE_NAME');

        foreach ($perObjectTables as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['csq', 'objects_install_data', 'requests', 'log', 'objects'] as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Same DDL as createObjectTables(), exposed as the NewObject / NewElekterObject
     * procedures ObjectController::store() calls, so objects created through the UI
     * also get their tables locally.
     */
    private function createProvisioningProcedures(): void
    {
        foreach (['NewObject' => false, 'NewElekterObject' => true] as $name => $withTariffs) {
            $statements = collect($this->objectTablesDdl('@n', $withTariffs))
                ->map(fn (string $ddl): string => "SET @s = CONCAT('" . str_replace('@n', "', @n, '", str_replace("'", "''", $ddl)) . "'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;")
                ->implode("\n    ");

            DB::unprepared("DROP PROCEDURE IF EXISTS `{$name}`");
            DB::unprepared("CREATE PROCEDURE `{$name}`(IN oid INT)\nBEGIN\n    SET @n = oid;\n    {$statements}\nEND");
        }
    }

    private function objectTablesDdl(string $n, bool $withTariffs): array
    {
        $tariffColumns = $withTariffs
            ? ', tariff_1 BIGINT NULL, tariff_2 BIGINT NULL, tariff_1_mvalue BIGINT NULL, tariff_2_mvalue BIGINT NULL'
            : '';

        return [
            "CREATE TABLE IF NOT EXISTS object_{$n} (id INT NOT NULL PRIMARY KEY, devid INT UNSIGNED NOT NULL, location VARCHAR(50) NOT NULL DEFAULT '', devtype INT NOT NULL DEFAULT 1, KEY devid (devid))",
            "CREATE TABLE IF NOT EXISTS lastdata_{$n} (devid INT UNSIGNED NOT NULL PRIMARY KEY, date DATETIME NULL, value BIGINT NULL, mvalue BIGINT NULL, prevVal BIGINT NULL, inserterd DATETIME NULL, error INT NULL, errorDate DATE NULL, statDate DATE NULL{$tariffColumns})",
            "CREATE TABLE IF NOT EXISTS mlog_{$n} (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, devid INT UNSIGNED NOT NULL, date DATETIME NOT NULL, value BIGINT NOT NULL, KEY devid_date (devid, date))",
            "CREATE TABLE IF NOT EXISTS flat_{$n} (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, location VARCHAR(50) NOT NULL, size DECIMAL(8,2) NOT NULL DEFAULT 0)",
            "CREATE TABLE IF NOT EXISTS heat_{$n} (devid INT UNSIGNED NOT NULL PRIMARY KEY, power INT NOT NULL DEFAULT 0, cof DECIMAL(6,3) NOT NULL DEFAULT 1, size DECIMAL(8,2) NOT NULL DEFAULT 0, description VARCHAR(255) NOT NULL DEFAULT '')",
        ];
    }

    private function createObjectTables(int $objectId, bool $withTariffs): void
    {
        foreach ($this->objectTablesDdl((string) $objectId, $withTariffs) as $ddl) {
            DB::statement($ddl);
        }
    }

    private function insertObject(int $index, string $address, string $city, float $lat, float $lon, int $dtype, int $flatCount, Carbon $now): int
    {
        // Mostly online; a few silent for days so the "offline" counter has something.
        $lastSession = match (true) {
            $index % 9 === 4 => $now->copy()->subDays(mt_rand(2, 20)),
            $index % 11 === 7 => null,
            default => $now->copy()->subMinutes(mt_rand(3, 600)),
        };

        $status = match ($index) {
            5, 17 => 2,
            22 => 0,
            default => 1,
        };

        $ver = match ($dtype) {
            2 => ['0.4.2', '0.5.1', '0.3.8', '0.5.0'][$index % 4],
            3 => '3.0.1',
            4 => 'L1.2',
            default => ['2.1.4', '2.2.0', '1.9.7'][$index % 3],
        };

        $imei = '86204904' . str_pad((string) (4965000 + $index * 17), 7, '0', STR_PAD_LEFT);
        $hasSecondModem = $index % 5 === 0;
        $phone = '+3725' . str_pad((string) (9100000 + $index * 731), 7, '0', STR_PAD_LEFT);

        return (int) DB::table('objects')->insertGetId([
            'address' => $address,
            'City' => $city,
            'GSMNR' => $phone,
            'GSMNR2' => $hasSecondModem ? '+3725' . (8200000 + $index) : '',
            'IMEI' => $imei,
            'IMEI2' => $hasSecondModem ? '86204904' . (5970000 + $index) : '',
            'Contact' => self::CONTACTS[$index % count(self::CONTACTS)],
            'Description' => sprintf("%d-этажный жилой дом, %d квартир\nСистема: %s", mt_rand(4, 9), $flatCount, self::SYSTEMS[$dtype]),
            'Description2' => 'Год постройки: ' . mt_rand(1962, 2008),
            'Company' => $index % 4 === 3 ? 2 : 1,
            'dtype' => $dtype,
            'status' => $status,
            'Devqtty' => (string) mt_rand(1, 3),
            'RadioDevQty' => (string) mt_rand(0, 4),
            'MainRadio' => (string) mt_rand(1, 3),
            'GSMSERIAL' => 'SIM' . str_pad((string) (1000 + $index), 6, '0', STR_PAD_LEFT),
            'GSMSERIAL2' => $hasSecondModem ? 'SIM' . str_pad((string) (5000 + $index), 6, '0', STR_PAD_LEFT) : '',
            'pin1' => (string) mt_rand(1000, 9999),
            'pin2' => (string) mt_rand(1000, 9999),
            'puk1' => (string) mt_rand(10000000, 99999999),
            'puk2' => (string) mt_rand(10000000, 99999999),
            'KeyCode' => strtoupper(bin2hex(random_bytes(4))),
            'manager' => (string) (($index % 4) + 1),
            'iPack' => 0,
            'packet' => ['basic', 'standard', 'premium'][$index % 3],
            'traffic' => [50, 100, 200, 500][$index % 4] . 'MB',
            'callCnt' => (string) mt_rand(0, 120),
            'summ' => number_format(mt_rand(800, 4500) / 100, 2, '.', ''),
            'csq' => $lastSession ? mt_rand(9, 31) : null,
            'ver' => $ver,
            'lastSession' => $lastSession,
            'selDate' => $index % 5 < 2 ? $now->copy()->subDays(mt_rand(1, 40)) : null,
            'lat' => $lat,
            'lon' => $lon,
            'saveHval' => $index % 2,
            'clientid' => 'C' . str_pad((string) (300 + $index), 5, '0', STR_PAD_LEFT),
            'token' => null,
            // SOE share split: percentage of the bill distributed by m2 (rest by allocators).
            'm2_andur' => [40, 50, 60, 30][$index % 4],
            'dataToPage' => $index % 3 === 0,
            'AddFee' => $index % 4 === 1 ? 1 : 0,
            'fee' => $index % 4 === 1 ? 12.50 : 0,
            'kuluM2' => $index % 6 === 0 ? 2 : 0,
            'AlgLopp' => $index % 3 === 1 ? 1 : 0,
            'created_at' => $now->copy()->subDays(mt_rand(120, 1500)),
            'updated_at' => $now->copy()->subDays(mt_rand(0, 30)),
        ]);
    }

    private function fillObjectTables(int $objectId, int $dtype, int $flatCount, Carbon $now): void
    {
        $flats = [];
        $devices = [];
        $heat = [];

        for ($flat = 1; $flat <= $flatCount; $flat++) {
            $location = (string) $flat;
            $flats[] = ['location' => $location, 'size' => mt_rand(2400, 9200) / 100];

            $kinds = match ($dtype) {
                3 => [4],                                                 // electricity meter
                4 => [1, 2],                                              // LoRa water: cold + hot
                default => array_merge([1, 2], array_fill(0, mt_rand(2, 4), 3)), // water + heat allocators
            };

            foreach ($kinds as $devtype) {
                $devid = $this->nextDevid += mt_rand(1, 40);
                $devices[] = ['id' => count($devices) + 1, 'devid' => $devid, 'location' => $location, 'devtype' => $devtype];

                if ($devtype === 3) {
                    $heat[] = [
                        'devid' => $devid,
                        'power' => mt_rand(4, 22) * 100,
                        'cof' => mt_rand(600, 1200) / 1000,
                        'size' => mt_rand(6, 24),
                        'description' => ['Tuba', 'Magamistuba', 'Köök', 'Elutuba', 'Vannituba'][mt_rand(0, 4)],
                    ];
                }
            }
        }

        DB::table('flat_' . $objectId)->insert($flats);
        DB::table('object_' . $objectId)->insert($devices);
        if ($heat !== []) {
            DB::table('heat_' . $objectId)->insert($heat);
        }

        $this->fillReadings($objectId, $devices, $dtype, $now);
    }

    /**
     * Daily cumulative readings in mlog_N plus the matching lastdata_N row.
     * Values are stored raw, i.e. multiplied by the device scale the app divides by
     * (water m3 * 1000, electricity kWh * 100, heat allocator units as-is).
     */
    private function fillReadings(int $objectId, array $devices, int $dtype, Carbon $now): void
    {
        $monthStart = $now->copy()->startOfMonth();
        $prevMonthStart = $monthStart->copy()->subMonth();
        $logRows = [];
        $lastRows = [];

        foreach ($devices as $device) {
            $roll = mt_rand(1, 100);

            // ~3% never reported, ~6% went silent some days ago.
            if ($roll <= 3) {
                continue;
            }
            $silentDays = $roll <= 9 ? mt_rand(4, 25) : 0;

            [$value, $dailyMin, $dailyMax] = match ($device['devtype']) {
                1 => [mt_rand(20, 600) * 1000, 60, 320],    // cold water, litres/day
                2 => [mt_rand(10, 300) * 1000, 30, 160],    // hot water, litres/day
                4 => [mt_rand(2000, 40000) * 100, 250, 1400], // electricity, 0.01 kWh/day
                default => [0, 0, 6],                        // heat allocator units/day
            };

            $mvalue = null;
            $prevVal = null;
            $lastDate = null;

            for ($day = self::HISTORY_DAYS; $day >= $silentDays; $day--) {
                $date = $now->copy()->startOfDay()->subDays($day)->addMinutes(mt_rand(0, 180));

                if ($date->greaterThan($now)) {
                    break;
                }

                if ($date->lessThan($prevMonthStart)) {
                    $prevVal = $value;
                }
                if ($date->lessThan($monthStart)) {
                    $mvalue = $value;
                }

                $logRows[] = ['devid' => $device['devid'], 'date' => $date->toDateTimeString(), 'value' => $value];
                $lastDate = $date;
                $value += mt_rand($dailyMin, $dailyMax);
            }

            $hasError = mt_rand(1, 100) <= 4;
            $row = [
                'devid' => $device['devid'],
                'date' => $lastDate?->toDateTimeString(),
                'value' => end($logRows)['value'],
                'mvalue' => $mvalue,
                'prevVal' => $prevVal,
                'inserterd' => $lastDate?->copy()->addMinutes(mt_rand(1, 30))->toDateTimeString(),
                'error' => $hasError ? self::ERROR_CODES[array_rand(self::ERROR_CODES)] : null,
                'errorDate' => $hasError ? $now->copy()->subDays(mt_rand(1, 60))->toDateString() : null,
                'statDate' => $monthStart->copy()->subDay()->toDateString(),
            ];

            if ($dtype === 3) {
                // Day/night split of the electricity meter.
                $row['tariff_1'] = (int) round($row['value'] * 0.62);
                $row['tariff_2'] = $row['value'] - $row['tariff_1'];
                $row['tariff_1_mvalue'] = (int) round(($mvalue ?? 0) * 0.62);
                $row['tariff_2_mvalue'] = ($mvalue ?? 0) - $row['tariff_1_mvalue'];
            }

            $lastRows[] = $row;
        }

        foreach (array_chunk($logRows, 2000) as $chunk) {
            DB::table('mlog_' . $objectId)->insert($chunk);
        }
        foreach (array_chunk($lastRows, 500) as $chunk) {
            DB::table('lastdata_' . $objectId)->insert($chunk);
        }
    }

    private function insertInstallData(int $objectId): void
    {
        $devices = DB::table('object_' . $objectId)->orderBy('id')->limit(mt_rand(3, 8))->get();
        $places = [['Vannituba', 'Külm vesi'], ['Köök', 'Soe vesi'], ['WC', 'Külm vesi'], ['Elutuba', 'Radiaator'], ['Koridor', 'Elektrikilp']];

        DB::table('objects_install_data')->insert($devices->map(function ($device) use ($objectId, $places): array {
            [$place1, $place2] = $places[mt_rand(0, count($places) - 1)];
            $installedAt = Carbon::now()->subDays(mt_rand(100, 900));

            return [
                'oid' => $objectId,
                'devid' => $device->devid,
                'location' => 'krt ' . $device->location,
                'devtype' => $device->devtype,
                'dnType' => [15, 20, 25][mt_rand(0, 2)],
                'len' => [80, 110, 130][mt_rand(0, 2)],
                'foto1' => mt_rand(0, 1),
                'foto2' => mt_rand(0, 1),
                'place1' => $place1,
                'place2' => $place2,
                'comment' => 'Paigaldatud ' . $installedAt->format('d.m.Y'),
                'created_at' => $installedAt,
                'updated_at' => $installedAt,
            ];
        })->all());
    }

    private function insertCsqHistory(int $objectId, Carbon $now): void
    {
        $base = mt_rand(12, 28);
        $rows = [];

        for ($hours = 7 * 24; $hours >= 0; $hours -= 6) {
            $at = $now->copy()->subHours($hours);
            $rows[] = ['object' => $objectId, 'csq' => max(5, min(31, $base + mt_rand(-4, 4))), 'created_at' => $at, 'updated_at' => $at];
        }

        DB::table('csq')->insert($rows);
    }

    private function insertRequests(Carbon $now): void
    {
        $objects = DB::table('objects')->pluck('IMEI', 'id');
        $commands = ['restart', 'clearRam', 'SendData', 'setSearch', 'setSearch2'];
        $rows = [];

        foreach ($objects as $id => $imei) {
            for ($i = mt_rand(0, 4); $i > 0; $i--) {
                $handled = mt_rand(1, 100) <= 80 ? 1 : 0;
                $rows[] = [
                    'object' => $id,
                    'IMEI' => substr($imei, 0, 15),
                    'content' => $commands[mt_rand(0, count($commands) - 1)],
                    'date' => $now->copy()->subHours(mt_rand(1, 24 * 30)),
                    'handled' => $handled,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('requests')->insert($rows);
        }
    }

    private function insertLog(Carbon $now): void
    {
        $objects = DB::table('objects')->pluck('IMEI', 'id')->all();
        $ids = array_keys($objects);
        $rows = [];

        for ($i = 0; $i < 40; $i++) {
            $id = $ids[mt_rand(0, count($ids) - 1)];
            $at = $now->copy()->subMinutes(mt_rand(5, 60 * 24 * 14));
            $rows[] = [
                'Content' => match (mt_rand(1, 6)) {
                    1 => sprintf('User %s logged in from 192.168.1.%d', ['admin', 'ivan', 'maria'][mt_rand(0, 2)], mt_rand(2, 250)),
                    2 => sprintf('Updated object #%d (IMEI: %s)', $id, $objects[$id]),
                    3 => sprintf('Checked object #%d (IMEI: %s)', $id, $objects[$id]),
                    4 => sprintf('Imported CSV for object #%d: %d readings', $id, mt_rand(10, 120)),
                    5 => sprintf('Command queued for object #%d', $id),
                    default => sprintf('Export requested for object #%d', $id),
                },
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        DB::table('log')->insert($rows);
    }
}
