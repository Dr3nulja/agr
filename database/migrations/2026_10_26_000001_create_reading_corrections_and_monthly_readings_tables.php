<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Журнал ручных правок показаний (mlog_N / lastdata_N): кто, когда, что было и что стало
        Schema::create('reading_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('object_id')->index();
            $table->unsignedBigInteger('devid');
            $table->string('action', 20);              // history_add | history_edit | history_delete | current_edit
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('user', 100)->nullable();
            $table->timestamps();

            $table->index(['object_id', 'devid']);
        });

        // Зафиксированные месячные показания: снимок lastdata_N на месяц,
        // который потом не меняют ни импорты, ни ручные правки истории
        Schema::create('monthly_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('object_id');
            $table->char('period', 7);                 // 2026-09
            $table->unsignedBigInteger('devid');
            $table->string('location', 15)->nullable();
            $table->unsignedTinyInteger('devtype');
            $table->dateTime('reading_date')->nullable();
            $table->bigInteger('value')->nullable();   // как в lastdata_N, без деления на масштаб
            $table->bigInteger('mvalue')->nullable();
            $table->bigInteger('prev_value')->nullable();
            $table->string('fixed_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['object_id', 'period', 'devid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_readings');
        Schema::dropIfExists('reading_corrections');
    }
};
