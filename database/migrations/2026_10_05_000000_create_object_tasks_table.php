<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Задачи для мобильного приложения (QRScannerApp): что делать на объекте.
     * Контора выставляет их в дашборде, приложение получает через /get_house_list.
     *
     * Без внешнего ключа на objects.id: в боевом дампе тип id может отличаться
     * от bigint, задачи удаляются вместе с объектом в ObjectController::destroy.
     */
    public function up(): void
    {
        Schema::create('object_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('object_id')->index();
            $table->string('device_type', 20); // allocator | water_meter
            $table->string('action_type', 20); // install | replace
            $table->timestamps();

            $table->unique(['object_id', 'device_type', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('object_tasks');
    }
};
