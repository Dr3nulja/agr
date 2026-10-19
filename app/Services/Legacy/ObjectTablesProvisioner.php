<?php

namespace App\Services\Legacy;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Таблицы нового объекта (object_N, lastdata_N, mlog_N, flat_N, heat_N).
 *
 * Старая система создавала их хранимыми процедурами NewObject / NewElekterObject.
 * Если процедура есть в базе — вызываем её. Если нет (в дампе agrBase от 07.07.2026
 * процедур нет) — создаём сами. DDL снят с самых новых объектов дампа:
 * #598 (Apator), #585 (Siemens), #591 (LoRa) — одинаковые; #515 (Elekter) — с тарифами.
 */
class ObjectTablesProvisioner
{
    public function provision(int $objectId, int $dtype): void
    {
        $procedure = $dtype === 3 ? 'NewElekterObject' : 'NewObject';

        if ($this->procedureExists($procedure)) {
            DB::statement("CALL {$procedure}(?)", [$objectId]);

            return;
        }

        foreach ($this->tables($objectId, $dtype === 3) as $ddl) {
            DB::statement($ddl);
        }

        if ($dtype !== 3) {
            $this->createPrevValTrigger($objectId);
        }
    }

    public function procedureExists(string $name): bool
    {
        return DB::table('information_schema.routines')
            ->where('routine_schema', DB::getDatabaseName())
            ->where('routine_type', 'PROCEDURE')
            ->where('routine_name', $name)
            ->exists();
    }

    private function tables(int $n, bool $electric): array
    {
        $lastdataValues = $electric
            ? '`tariff_1` int unsigned DEFAULT 0,
               `tariff_2` int unsigned DEFAULT 0,
               `inserterd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
               `tariff_1_mvalue` int unsigned DEFAULT NULL,
               `tariff_2_mvalue` int unsigned DEFAULT NULL,
               `tariff_1_prevVal` int unsigned DEFAULT NULL,
               `tariff_2_prevVal` int unsigned DEFAULT NULL,'
            : '`value` int NOT NULL,
               `inserterd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
               `mvalue` int DEFAULT 0,
               `prevVal` int DEFAULT 0,';

        $mlogValues = $electric
            ? '`tariff_1` int unsigned NOT NULL,
               `tariff_2` int unsigned NOT NULL,
               `value` int DEFAULT NULL,'
            : '`value` int NOT NULL,';

        return [
            "CREATE TABLE IF NOT EXISTS `object_{$n}` (
                `id` int NOT NULL AUTO_INCREMENT,
                `devid` int NOT NULL,
                `location` varchar(15) DEFAULT NULL,
                `devtype` tinyint NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `devid` (`devid`),
                KEY `devtype` (`devtype`),
                KEY `devIdtype` (`devid`, `devtype`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3",

            "CREATE TABLE IF NOT EXISTS `lastdata_{$n}` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `devid` int unsigned NOT NULL,
                `date` datetime NOT NULL,
                {$lastdataValues}
                `error` smallint DEFAULT NULL,
                `errorDate` datetime DEFAULT NULL,
                `prevYearVal` int DEFAULT NULL,
                `prevYearDate` datetime DEFAULT NULL,
                `statDate` date DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `devid` (`devid`),
                KEY `date` (`date`),
                KEY `error` (`error`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3",

            "CREATE TABLE IF NOT EXISTS `mlog_{$n}` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `devid` int unsigned NOT NULL,
                `date` datetime NOT NULL,
                {$mlogValues}
                `inserterd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `devid` (`devid`),
                KEY `date` (`date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3",

            "CREATE TABLE IF NOT EXISTS `flat_{$n}` (
                `id` int NOT NULL AUTO_INCREMENT,
                `location` varchar(5) NOT NULL,
                `size` float NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3",

            "CREATE TABLE IF NOT EXISTS `heat_{$n}` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `devid` int NOT NULL,
                `power` smallint unsigned NOT NULL,
                `cof` float NOT NULL,
                `size` varchar(20) NOT NULL,
                `description` text,
                PRIMARY KEY (`id`),
                KEY `devid` (`devid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3",
        ];
    }

    /**
     * Как ins_data_N в старой базе: при новом месячном значении старое уходит в prevVal.
     * Без прав на CREATE TRIGGER (или при binlog без log_bin_trust_function_creators)
     * объект всё равно создаётся — пишем предупреждение в лог.
     */
    private function createPrevValTrigger(int $n): void
    {
        $exists = DB::table('information_schema.triggers')
            ->where('trigger_schema', DB::getDatabaseName())
            ->where('trigger_name', "ins_data_{$n}")
            ->exists();

        if ($exists) {
            return;
        }

        try {
            DB::unprepared("CREATE TRIGGER `ins_data_{$n}` BEFORE UPDATE ON `lastdata_{$n}` FOR EACH ROW
                BEGIN
                    IF (NEW.mvalue != OLD.mvalue) THEN
                        SET NEW.prevVal = OLD.mvalue;
                    END IF;
                END");
        } catch (QueryException $e) {
            Log::warning("Trigger ins_data_{$n} was not created: " . $e->getMessage());
        }
    }
}
