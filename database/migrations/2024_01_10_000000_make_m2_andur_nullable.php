<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE `objects` MODIFY `m2_andur` TINYINT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `objects` MODIFY `m2_andur` INT NOT NULL DEFAULT 0');
    }
};
