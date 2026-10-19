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
     * Переносим уже записанные в объектах карты. Один номер/ICCID бывает записан
     * на нескольких объектах (SIM переставили, старую запись не почистили — в дампе
     * таких 13). Карта достаётся объекту, где она скорее всего стоит сейчас:
     * активному и позже всех выходившему на связь. Остальные объекты указываются
     * в комментарии карты; их колонки в objects не трогаем.
     */
    private function importFromObjects(): void
    {
        $seenNumbers = [];
        $seenIccids = [];
        $now = now();

        $objects = DB::table('objects')
            ->orderByRaw('status = 1 DESC')
            ->orderByRaw('lastSession IS NULL')
            ->orderByDesc('lastSession')
            ->orderBy('id')
            ->get(['id', 'GSMNR', 'GSMSERIAL', 'GSMNR2', 'GSMSERIAL2', 'pin1', 'pin2', 'puk1', 'puk2']);

        foreach ($objects as $object) {
            $slots = [
                1 => [$object->GSMNR, $object->GSMSERIAL, $object->pin1, $object->pin2, $object->puk1, $object->puk2],
                2 => [$object->GSMNR2 ?? null, $object->GSMSERIAL2, null, null, null, null],
            ];

            foreach ($slots as $slot => [$number, $iccid, $pin1, $pin2, $puk1, $puk2]) {
                $number = $this->cleanId($number);
                $iccid = $this->cleanId($iccid);

                if ($number === null && $iccid === null) {
                    continue;
                }
                $ownerSimId = ($number !== null ? ($seenNumbers[mb_strtolower($number)] ?? null) : null)
                    ?? ($iccid !== null ? ($seenIccids[mb_strtolower($iccid)] ?? null) : null);
                if ($ownerSimId) {
                    DB::table('sim_cards')->where('id', $ownerSimId)->update([
                        'comment' => DB::raw("CONCAT_WS('; ', comment, " . DB::getPdo()->quote(sprintf('Sama SIM oli kirjas ka objektil #%d', $object->id)) . ')'),
                    ]);
                    continue;
                }

                $simId = DB::table('sim_cards')->insertGetId([
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
                    $seenNumbers[mb_strtolower($number)] = $simId;
                }
                if ($iccid !== null) {
                    $seenIccids[mb_strtolower($iccid)] = $simId;
                }
            }
        }
    }

    /**
     * Номер / ICCID: без пробелов и невидимых символов (в дампе есть номера,
     * вставленные с U+202A/U+202C — для MySQL они равны «чистым», для PHP нет)
     */
    private function cleanId(?string $value): ?string
    {
        return $this->clean(preg_replace('/[\p{Cf}\s]+/u', '', (string) $value));
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
