<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Services\Legacy\LegacyObjectsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesLegacyObjectTables;
use Tests\TestCase;

class SoeCostCalculationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesLegacyObjectTables;

    /**
     * A single apartment with a single Apator heat allocator, 50/50 m2-vs-sensor
     * split: the whole payment must land on that one apartment, split evenly.
     */
    public function test_single_apartment_single_radiator_splits_evenly(): void
    {
        $object = AgrObject::create([
            'address' => 'Test Street 3',
            'City' => 'Tallinn',
            'IMEI' => '333333333333333',
            'dtype' => 1, // Apator
            'status' => 1,
            'm2_andur' => 50, // 50% by area, 50% by sensor reading
        ]);

        $this->createLegacyObjectTables($object->id);

        DB::table('flat_' . $object->id)->insert([
            'location' => 'Apt1',
            'size' => 100,
        ]);

        DB::table('object_' . $object->id)->insert([
            'devid' => 7001,
            'location' => 'Apt1',
            'devtype' => 3,
        ]);

        DB::table('heat_' . $object->id)->insert([
            'devid' => 7001,
            'power' => 1000,
            'cof' => 1.0,
            'size' => '10',
            'description' => '',
        ]);

        // Apator devices are read as an absolute lifetime value; a reading
        // dated in a past month makes the calculator use `value` (not `mvalue`).
        DB::table('lastdata_' . $object->id)->insert([
            'devid' => 7001,
            'date' => '2026-07-01 00:00:00',
            'value' => 300,
            'mvalue' => 999, // must be ignored for month=8 with a July reading
            'prevVal' => 0,
        ]);

        $report = app(LegacyObjectsService::class)->calculateSoeCosts($object->id, 8, 100.0);

        $this->assertCount(1, $report['flats']);
        $flat = $report['flats'][0];

        $this->assertEqualsWithDelta(50.0, $flat['m2_value'], 0.01);
        $this->assertEqualsWithDelta(50.0, $flat['sensor_value'], 0.01);
        $this->assertEqualsWithDelta(100.0, $flat['total'], 0.01);
        $this->assertEqualsWithDelta(100.0, $report['grand']['total'], 0.01);

        $this->dropLegacyObjectTables($object->id);
    }

    /**
     * Two apartments of unequal size, no sensors at all: cost must split
     * purely by area (m2), nothing lost or double-counted.
     */
    public function test_cost_splits_by_area_when_no_radiators_present(): void
    {
        $object = AgrObject::create([
            'address' => 'Test Street 4',
            'City' => 'Tallinn',
            'IMEI' => '444444444444444',
            'dtype' => 1,
            'status' => 1,
            'm2_andur' => 100, // 100% by area
        ]);

        $this->createLegacyObjectTables($object->id);

        DB::table('flat_' . $object->id)->insert([
            ['location' => 'Apt1', 'size' => 30],
            ['location' => 'Apt2', 'size' => 70],
        ]);

        $report = app(LegacyObjectsService::class)->calculateSoeCosts($object->id, 8, 100.0);

        $byLocation = collect($report['flats'])->keyBy('location');

        $this->assertEqualsWithDelta(30.0, $byLocation['Apt1']['total'], 0.01);
        $this->assertEqualsWithDelta(70.0, $byLocation['Apt2']['total'], 0.01);

        $this->dropLegacyObjectTables($object->id);
    }
}
