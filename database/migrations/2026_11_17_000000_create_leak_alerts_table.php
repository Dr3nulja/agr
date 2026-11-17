<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Найденные утечки: водомер насчитал за сутки ≥ порога (config agr.leak_daily_m3).
     * Одна запись на прибор и день — повторный запуск проверки дубликатов не создаёт.
     */
    public function up(): void
    {
        Schema::create('leak_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('object_id')->index();
            $table->unsignedInteger('devid');
            $table->string('location', 15)->nullable();
            $table->unsignedTinyInteger('devtype');
            $table->date('day');
            $table->decimal('consumption', 12, 3);   // м³ за сутки
            $table->decimal('from_value', 12, 3);    // показание в начале
            $table->decimal('to_value', 12, 3);      // показание в конце дня
            $table->dateTime('from_date');
            $table->dateTime('to_date');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by', 100)->nullable();
            $table->timestamps();

            $table->unique(['object_id', 'devid', 'day']);
            $table->index(['resolved_at', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leak_alerts');
    }
};
