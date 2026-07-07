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
        Schema::create('objects_install_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oid')->index();
            $table->integer('devid')->index();
            $table->string('location')->nullable();
            $table->integer('devtype')->default(0);
            $table->integer('dnType')->default(0);
            $table->integer('len')->default(0);
            $table->integer('foto1')->default(0);
            $table->integer('foto2')->default(0);
            $table->string('place1')->nullable();
            $table->string('place2')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            
            $table->foreign('oid')->references('id')->on('objects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objects_install_data');
    }
};
