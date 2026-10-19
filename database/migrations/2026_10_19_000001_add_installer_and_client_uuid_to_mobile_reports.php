<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * installer   — имя монтажника из приложения (кто сделал отчёт)
     * client_uuid — id отчёта, который генерирует приложение: повторная отправка
     *               после обрыва связи не создаёт дубль
     */
    public function up(): void
    {
        Schema::table('mobile_reports', function (Blueprint $table) {
            $table->string('installer', 100)->nullable()->after('object_id');
            $table->string('client_uuid', 64)->nullable()->unique()->after('installer');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_reports', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn(['installer', 'client_uuid']);
        });
    }
};
