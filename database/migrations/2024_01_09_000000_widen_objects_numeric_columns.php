<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Several `objects` columns are stored as free-form varchar in the real
     * production schema (blank strings, non-numeric values) even though this
     * migration originally typed them as integer/decimal. Widen them so real
     * rows can be inserted as-is.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `objects` MODIFY `MainRadio` VARCHAR(50) NULL');
        DB::statement('ALTER TABLE `objects` MODIFY `manager` VARCHAR(1) NULL');
        DB::statement('ALTER TABLE `objects` MODIFY `Devqtty` VARCHAR(15) NULL');
        DB::statement('ALTER TABLE `objects` MODIFY `RadioDevQty` VARCHAR(15) NULL');
        DB::statement('ALTER TABLE `objects` MODIFY `callCnt` VARCHAR(5) NULL');
        DB::statement('ALTER TABLE `objects` MODIFY `summ` VARCHAR(10) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `objects` MODIFY `MainRadio` INT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `objects` MODIFY `manager` INT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `objects` MODIFY `Devqtty` INT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `objects` MODIFY `RadioDevQty` INT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `objects` MODIFY `callCnt` INT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `objects` MODIFY `summ` DECIMAL(10,2) NULL DEFAULT 0');
    }
};
