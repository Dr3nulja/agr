<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Отчёты монтажников из мобильного приложения (QRScannerApp, POST /insert_dev_data).
     *
     * Основные поля — отдельными колонками (по ним ищем и фильтруем),
     * всё остальное из формы — в `data` (JSON), чтобы новые поля в приложении
     * не требовали миграций. Фото и подпись лежат файлами в storage/app/mobile_reports (не публично).
     */
    public function up(): void
    {
        Schema::create('mobile_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('object_id')->index();
            $table->string('device_type', 20);  // allocator | water_meter
            $table->string('action_type', 20);  // install | replace
            $table->string('apartment', 50)->nullable();
            $table->string('qr_id', 100)->nullable()->index();
            $table->string('last_reading', 50)->nullable();
            $table->json('data')->nullable();
            $table->string('photo_before')->nullable();
            $table->string('photo_after')->nullable();
            $table->string('signature')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_reports');
    }
};
