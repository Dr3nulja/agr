<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Регистр SIM-карт. Раньше SIM жила только в колонках objects
     * (GSMNR/GSMSERIAL/pin/puk для модема 1, GSMNR2/GSMSERIAL2 для модема 2).
     * Теперь каждая карта — строка здесь, а объекту она назначается в слот 1 или 2.
     * Колонки в objects остаются копией назначенных карт (SimCardService::syncObject),
     * чтобы легаси-код, читающий objects.GSMNR, продолжал работать.
     */
    public function up(): void
    {
        Schema::create('sim_cards', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->nullable()->unique();   // телефонный номер (GSMNR)
            $table->string('iccid', 50)->nullable()->unique();    // серийный номер карты (GSMSERIAL)
            $table->string('pin1', 50)->nullable();
            $table->string('pin2', 50)->nullable();
            $table->string('puk1', 50)->nullable();
            $table->string('puk2', 50)->nullable();
            $table->string('status', 10)->default('free')->index(); // free | issued | assigned
            $table->string('issued_to', 100)->nullable();          // техник, которому выдана
            $table->timestamp('issued_at')->nullable();
            $table->unsignedBigInteger('object_id')->nullable()->index();
            $table->unsignedTinyInteger('slot')->nullable();        // 1 = GSMNR, 2 = GSMNR2
            $table->timestamp('assigned_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['object_id', 'slot']);
        });

        $this->importFromObjects();
    }

    /**
     * Переносим уже записанные в объектах карты. Если один номер/ICCID стоит
     * на нескольких объектах, карта назначается первому, остальные пропускаются —
     * их колонки в objects не трогаем, разобраться можно вручную.
     */
    private function importFromObjects(): void
    {
        $seenNumbers = [];
        $seenIccids = [];
        $now = now();

        $objects = DB::table('objects')
            ->orderBy('id')
            ->get(['id', 'GSMNR', 'GSMSERIAL', 'GSMNR2', 'GSMSERIAL2', 'pin1', 'pin2', 'puk1', 'puk2']);

        foreach ($objects as $object) {
            $slots = [
                1 => [$object->GSMNR, $object->GSMSERIAL, $object->pin1, $object->pin2, $object->puk1, $object->puk2],
                2 => [$object->GSMNR2 ?? null, $object->GSMSERIAL2, null, null, null, null],
            ];

            foreach ($slots as $slot => [$number, $iccid, $pin1, $pin2, $puk1, $puk2]) {
                $number = $this->clean($number);
                $iccid = $this->clean($iccid);

                if ($number === null && $iccid === null) {
                    continue;
                }
                if (($number !== null && isset($seenNumbers[$number])) || ($iccid !== null && isset($seenIccids[$iccid]))) {
                    continue;
                }

                DB::table('sim_cards')->insert([
                    'number' => $number,
                    'iccid' => $iccid,
                    'pin1' => $this->clean($pin1),
                    'pin2' => $this->clean($pin2),
                    'puk1' => $this->clean($puk1),
                    'puk2' => $this->clean($puk2),
                    'status' => 'assigned',
                    'object_id' => $object->id,
                    'slot' => $slot,
                    'assigned_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($number !== null) {
                    $seenNumbers[$number] = true;
                }
                if ($iccid !== null) {
                    $seenIccids[$iccid] = true;
                }
            }
        }
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '0' ? null : mb_substr($value, 0, 50);
    }

    public function down(): void
    {
        Schema::dropIfExists('sim_cards');
    }
};
