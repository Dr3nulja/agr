<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesLegacyObjectTables;
use Tests\TestCase;

class ObjectDeviceScaleTest extends TestCase
{
    use RefreshDatabase;
    use CreatesLegacyObjectTables;

    /**
     * Regression test: water meter readings (devtype 1/2) are stored as raw
     * integers scaled x1000 (matches AllObjPage.class.php's legacy display
     * switch). A wrong scale factor here silently displayed readings 1000x
     * too large - e.g. "125400" instead of "125.4" m3.
     */
    public function test_water_meter_reading_is_displayed_scaled_down_by_1000(): void
    {
        $object = AgrObject::create([
            'address' => 'Test Street 1',
            'City' => 'Tallinn',
            'IMEI' => '111111111111111',
            'dtype' => 1,
            'status' => 1,
        ]);

        $this->createLegacyObjectTables($object->id);

        DB::table('object_' . $object->id)->insert([
            'devid' => 5001,
            'location' => 'Apt 1',
            'devtype' => 1,
        ]);

        DB::table('lastdata_' . $object->id)->insert([
            'devid' => 5001,
            'date' => now(),
            'value' => 125400,
            'mvalue' => 125400,
            'prevVal' => 118200,
        ]);

        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);

        $response = $this->withSession(['user_id' => $user->id, 'user' => $user])
            ->get('/objects/' . $object->id);

        $response->assertOk();
        $response->assertSee('125.4');
        $response->assertDontSee('125400.000');

        $this->dropLegacyObjectTables($object->id);
    }

    /**
     * Heat devices (devtype 3) are stored unscaled - make sure the water-meter
     * fix didn't accidentally start dividing these too.
     */
    public function test_heat_meter_reading_is_displayed_unscaled(): void
    {
        $object = AgrObject::create([
            'address' => 'Test Street 2',
            'City' => 'Tallinn',
            'IMEI' => '222222222222222',
            'dtype' => 2,
            'status' => 1,
        ]);

        $this->createLegacyObjectTables($object->id);

        DB::table('object_' . $object->id)->insert([
            'devid' => 6001,
            'location' => 'Apt 1',
            'devtype' => 3,
        ]);

        DB::table('lastdata_' . $object->id)->insert([
            'devid' => 6001,
            'date' => now(),
            'value' => 250,
            'mvalue' => 250,
            'prevVal' => 200,
        ]);

        $user = User::create(['name' => 'Admin', 'login' => 'admin2', 'pass' => 'secret', 'role' => 1]);

        $response = $this->withSession(['user_id' => $user->id, 'user' => $user])
            ->get('/objects/' . $object->id);

        $response->assertOk();
        $response->assertSee('250.000');

        $this->dropLegacyObjectTables($object->id);
    }
}
