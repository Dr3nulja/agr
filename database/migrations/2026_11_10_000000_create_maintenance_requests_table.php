<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hooldus: заявки на обслуживание из мобильного приложения (POST /insert_hooldus).
     * Адрес вводится вручную, к объекту не привязан — дом может ещё не быть в системе.
     */
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 10);  // water | heat
            $table->string('address');
            $table->text('comment')->nullable();
            $table->string('installer', 100)->nullable();
            $table->string('client_uuid', 64)->nullable()->unique();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
