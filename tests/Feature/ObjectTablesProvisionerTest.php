<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ObjectTablesProvisionerTest extends TestCase
{
    use RefreshDatabase;

    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $id) {
            foreach (['object', 'lastdata', 'mlog', 'flat', 'heat'] as $prefix) {
                Schema::dropIfExists("{$prefix}_{$id}");
            }
        }
        parent::tearDown();
    }

    private function createObject(string $imei, int $dtype): AgrObject
    {
        $user = User::create(['name' => 'Admin', 'login' => 'admin' . $imei, 'pass' => 'x', 'role' => 1]);

        $this->withSession(['user_id' => $user->id, 'user' => $user])
            ->post('/objects', ['address' => 'Uus tn ' . $dtype, 'City' => 'Tallinn', 'IMEI' => $imei, 'dtype' => $dtype])
            ->assertRedirect(route('objects.index'));

        $object = AgrObject::where('IMEI', $imei)->firstOrFail();
        $this->created[] = $object->id;

        return $object;
    }

    /**
     * В дампе agrBase нет процедуры NewObject — таблицы объекта создаёт сам сайт,
     * со схемой и триггером как у последних объектов старой системы.
     */
    public function test_new_object_gets_its_tables_without_stored_procedure(): void
    {
        $object = $this->createObject('930000000000001', 1);
        $id = $object->id;

        foreach (['object', 'lastdata', 'mlog', 'flat', 'heat'] as $prefix) {
            $this->assertTrue(Schema::hasTable("{$prefix}_{$id}"), "{$prefix}_{$id} missing");
        }
        $this->assertTrue(Schema::hasColumns("lastdata_{$id}", ['mvalue', 'prevVal', 'prevYearVal', 'statDate']));

        // Триггер ins_data_N: новое месячное значение сдвигает старое в prevVal
        DB::table("lastdata_{$id}")->insert(['devid' => 7, 'date' => now(), 'value' => 100, 'mvalue' => 50]);
        DB::table("lastdata_{$id}")->where('devid', 7)->update(['mvalue' => 80]);
        $this->assertSame(50, (int) DB::table("lastdata_{$id}")->where('devid', 7)->value('prevVal'));

        // Страница объекта работает с созданными таблицами
        $this->get('/objects/' . $id)->assertOk();
    }

    public function test_electric_object_gets_tariff_tables(): void
    {
        $id = $this->createObject('930000000000002', 3)->id;

        $this->assertTrue(Schema::hasColumns("lastdata_{$id}", ['tariff_1', 'tariff_2', 'tariff_1_prevVal']));
        $this->assertTrue(Schema::hasColumns("mlog_{$id}", ['tariff_1', 'tariff_2']));
    }
}
