<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Код работает со схемой боевой базы (дамп agrBase). Здесь:
     *  - локальная база из миграций получает log.ins_date вместо created_at/updated_at,
     *    как в боевой (модель Log пишет ins_date);
     *  - в боевой objects.pin1/pin2 = varchar(4): PIN бывает до 8 цифр, а регистр SIM
     *    копирует PIN в objects — расширяем (только расширение, данные не меняются).
     */
    public function up(): void
    {
        if (Schema::hasColumn('log', 'created_at') && ! Schema::hasColumn('log', 'ins_date')) {
            DB::statement('ALTER TABLE `log` CHANGE `created_at` `ins_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
        if (Schema::hasColumn('log', 'updated_at')) {
            DB::statement('ALTER TABLE `log` DROP COLUMN `updated_at`');
        }

        foreach (['pin1' => 8, 'pin2' => 8, 'puk1' => 10, 'puk2' => 10] as $column => $length) {
            if ($this->columnLength('objects', $column) < $length) {
                DB::statement("ALTER TABLE `objects` MODIFY `{$column}` VARCHAR({$length}) NULL");
            }
        }
    }

    private function columnLength(string $table, string $column): int
    {
        return (int) DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('CHARACTER_MAXIMUM_LENGTH');
    }

    public function down(): void
    {
        // Расширение колонок не откатываем: сужение может обрезать данные
    }
};
