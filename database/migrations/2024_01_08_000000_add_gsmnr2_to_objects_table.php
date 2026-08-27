<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('objects', 'GSMNR2')) {
            Schema::table('objects', function (Blueprint $table) {
                $table->string('GSMNR2')->nullable()->after('GSMNR');
            });
        }
    }

    public function down(): void
    {
        Schema::table('objects', function (Blueprint $table) {
            $table->dropColumn('GSMNR2');
        });
    }
};
