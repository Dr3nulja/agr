<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * The legacy app provisions one object_N/lastdata_N/mlog_N/flat_N/heat_N set of
 * tables per object via a MySQL stored procedure (NewObject/NewElekterObject) -
 * there's no Laravel migration for them. Tests that exercise device readings,
 * exports, or SOE calculations need to stand these up manually, matching the
 * real production schema (see database dump comparison).
 */
trait CreatesLegacyObjectTables
{
    protected function createLegacyObjectTables(int $objectId): void
    {
        DB::statement("CREATE TABLE object_{$objectId} (
            id int NOT NULL AUTO_INCREMENT,
            devid int NOT NULL,
            location varchar(15) DEFAULT NULL,
            devtype tinyint NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY devid (devid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

        DB::statement("CREATE TABLE lastdata_{$objectId} (
            id int unsigned NOT NULL AUTO_INCREMENT,
            devid int unsigned NOT NULL,
            date datetime NOT NULL,
            value int NOT NULL,
            inserterd datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            mvalue int DEFAULT '0',
            prevVal int DEFAULT '0',
            error smallint DEFAULT NULL,
            errorDate datetime DEFAULT NULL,
            statDate date DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY devid (devid)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

        DB::statement("CREATE TABLE mlog_{$objectId} (
            id int unsigned NOT NULL AUTO_INCREMENT,
            devid int unsigned NOT NULL,
            date datetime NOT NULL,
            value int NOT NULL,
            inserterd datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

        DB::statement("CREATE TABLE flat_{$objectId} (
            id int NOT NULL AUTO_INCREMENT,
            location varchar(5) NOT NULL,
            size float NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");

        DB::statement("CREATE TABLE heat_{$objectId} (
            id int unsigned NOT NULL AUTO_INCREMENT,
            devid int NOT NULL,
            power smallint unsigned NOT NULL,
            cof float NOT NULL,
            size varchar(20) NOT NULL,
            description text,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3");
    }

    protected function dropLegacyObjectTables(int $objectId): void
    {
        foreach (['object', 'lastdata', 'mlog', 'flat', 'heat'] as $prefix) {
            DB::statement("DROP TABLE IF EXISTS {$prefix}_{$objectId}");
        }
    }
}
