<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Два чека вместо одного: в доме с водой и теплом сначала сдают воду
     * (water_checked_at), потом тепло (heat_checked_at). selDate, как и раньше,
     * значит «сдано всё» — его читают фильтры и легаси-код.
     * report_emails — куда отправлять отчёты, несколько адресов через запятую.
     */
    public function up(): void
    {
        Schema::table('objects', function (Blueprint $table) {
            if (! Schema::hasColumn('objects', 'water_checked_at')) {
                $table->dateTime('water_checked_at')->nullable();
            }
            if (! Schema::hasColumn('objects', 'heat_checked_at')) {
                $table->dateTime('heat_checked_at')->nullable();
            }
            if (! Schema::hasColumn('objects', 'report_emails')) {
                $table->string('report_emails', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('objects', function (Blueprint $table) {
            $table->dropColumn(['water_checked_at', 'heat_checked_at', 'report_emails']);
        });
    }
};
