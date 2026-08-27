<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align a few `objects` columns with the real production schema so rows
     * imported from the live dump (firmware version strings, csq, iPack,
     * clientid, token) can be inserted as-is.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `objects` MODIFY `ver` VARCHAR(20) NULL');

        if (! Schema::hasColumn('objects', 'csq')) {
            DB::statement('ALTER TABLE `objects` ADD COLUMN `csq` TINYINT UNSIGNED NULL AFTER `summ`');
        }
        if (! Schema::hasColumn('objects', 'iPack')) {
            DB::statement('ALTER TABLE `objects` ADD COLUMN `iPack` TINYINT UNSIGNED NULL DEFAULT 0 AFTER `manager`');
        }
        if (! Schema::hasColumn('objects', 'clientid')) {
            DB::statement('ALTER TABLE `objects` ADD COLUMN `clientid` VARCHAR(20) NULL AFTER `saveHval`');
        }
        if (! Schema::hasColumn('objects', 'token')) {
            DB::statement('ALTER TABLE `objects` ADD COLUMN `token` VARCHAR(200) NULL AFTER `clientid`');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `objects` MODIFY `ver` INT NULL DEFAULT 0');
        Schema::table('objects', function ($table) {
            $table->dropColumn(['csq', 'iPack', 'clientid', 'token']);
        });
    }
};
