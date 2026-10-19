<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * В боевой базе users.pass = varchar(50) под MD5. При входе AuthController переводит
     * пароль на bcrypt (60 символов) — без расширения вход падал с «Data too long».
     */
    public function up(): void
    {
        $length = (int) DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'users')
            ->where('column_name', 'pass')
            ->value('CHARACTER_MAXIMUM_LENGTH');

        if ($length < 255) {
            DB::statement('ALTER TABLE `users` MODIFY `pass` VARCHAR(255) NOT NULL');
        }
    }

    public function down(): void
    {
        // Не сужаем: bcrypt-хэши не поместятся обратно
    }
};
