<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('objects', function (Blueprint $table) {
            $table->id();
            $table->string('address')->index();
            $table->string('City')->nullable();
            $table->string('GSMNR')->nullable();
            $table->string('IMEI')->nullable()->unique();
            $table->string('IMEI2')->nullable();
            $table->string('Contact')->nullable();
            $table->text('Description')->nullable();
            $table->text('Description2')->nullable();
            $table->integer('Company')->default(0)->index();
            $table->integer('dtype')->default(0)->index();
            $table->integer('status')->default(1);
            $table->integer('Devqtty')->default(0);
            $table->integer('RadioDevQty')->default(0);
            $table->integer('MainRadio')->default(0);
            $table->string('GSMSERIAL')->nullable();
            $table->string('GSMSERIAL2')->nullable();
            $table->string('pin1')->nullable();
            $table->string('pin2')->nullable();
            $table->string('puk1')->nullable();
            $table->string('puk2')->nullable();
            $table->string('KeyCode')->nullable();
            $table->integer('manager')->default(0)->index();
            $table->string('packet')->nullable();
            $table->string('traffic')->nullable();
            $table->integer('callCnt')->default(0);
            $table->decimal('summ', 10, 2)->default(0);
            $table->integer('ver')->default(0);
            $table->dateTime('lastSession')->nullable();
            $table->dateTime('selDate')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->boolean('saveHval')->default(false);
            $table->integer('m2_andur')->default(0);
            $table->boolean('dataToPage')->default(false);
            $table->decimal('AddFee', 10, 2)->default(0);
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('kuluM2', 10, 2)->default(0);
            $table->integer('AlgLopp')->default(0);
            $table->timestamps();
            
            $table->index(['Company', 'dtype', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objects');
    }
};
