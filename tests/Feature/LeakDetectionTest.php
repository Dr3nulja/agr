<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Models\LeakAlert;
use App\Models\User;
use App\Services\LeakDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesLegacyObjectTables;
use Tests\TestCase;

class LeakDetectionTest extends TestCase
{
    use RefreshDatabase;
    use CreatesLegacyObjectTables;

    private array $legacyObjects = [];

    protected function tearDown(): void
    {
        foreach ($this->legacyObjects as $objectId) {
            $this->dropLegacyObjectTables($objectId);
        }

        parent::tearDown();
    }

    /**
     * Дом с водомерами: [devid => [квартира, devtype]], история mlog: [[devid, date, value в литрах]]
     */
    private function house(array $devices, array $history): AgrObject
    {
        $object = AgrObject::create([
            'address' => 'Lekke tn ' . mt_rand(1, 999),
            'City' => 'Tallinn',
            'IMEI' => (string) mt_rand(100000000, 999999999),
            'dtype' => 1,
            'status' => 1,
            'Company' => 1,
            'manager' => 1,
        ]);
        $this->createLegacyObjectTables($object->id);
        $this->legacyObjects[] = $object->id;

        foreach ($devices as $devid => [$location, $devtype]) {
            DB::table('object_' . $object->id)->insert(['devid' => $devid, 'location' => $location, 'devtype' => $devtype]);
        }
        foreach ($history as [$devid, $date, $value]) {
            DB::table('mlog_' . $object->id)->insert(['devid' => $devid, 'date' => $date, 'value' => $value]);
        }

        return $object;
    }

    public function test_meter_with_five_cubes_a_day_is_a_leak(): void
    {
        $object = $this->house(
            [1001 => ['5', 1], 1002 => ['6', 2], 3001 => ['7', 3]],
            [
                // 1001: 100.000 → 106.200 за сутки — утечка
                [1001, '2026-11-15 23:00:00', 100000],
                [1001, '2026-11-16 12:00:00', 103000],
                [1001, '2026-11-16 23:00:00', 106200],
                // 1002: 0.3 м³ — норма
                [1002, '2026-11-15 23:00:00', 50000],
                [1002, '2026-11-16 23:00:00', 50300],
                // Jaotur не проверяется
                [3001, '2026-11-15 23:00:00', 0],
                [3001, '2026-11-16 23:00:00', 900000],
            ],
        );

        $found = app(LeakDetector::class)->scanDay(Carbon::parse('2026-11-16'));

        $this->assertCount(1, $found);
        $this->assertSame(1001, $found[0]->devid);
        $this->assertSame('5', $found[0]->location);
        $this->assertEqualsWithDelta(6.2, $found[0]->consumption, 0.0001);
        $this->assertSame($object->id, $found[0]->object_id);

        // Повторный запуск не создаёт дубликат
        $this->assertCount(0, app(LeakDetector::class)->scanDay(Carbon::parse('2026-11-16')));
        $this->assertSame(1, LeakAlert::count());
    }

    public function test_exactly_threshold_is_leak_and_below_is_not(): void
    {
        $this->house(
            [1001 => ['1', 1], 1002 => ['2', 1]],
            [
                [1001, '2026-11-15 23:00:00', 10000],
                [1001, '2026-11-16 23:00:00', 15000],   // ровно 5 м³
                [1002, '2026-11-15 23:00:00', 10000],
                [1002, '2026-11-16 23:00:00', 14999],   // 4.999 м³
            ],
        );

        $found = app(LeakDetector::class)->scanDay(Carbon::parse('2026-11-16'));

        $this->assertSame([1001], array_map(fn ($alert) => $alert->devid, $found));
    }

    public function test_gap_in_data_is_averaged_per_day_and_meter_reset_is_ignored(): void
    {
        $this->house(
            [1001 => ['1', 1], 1002 => ['2', 1]],
            [
                // Связи не было 3 дня: 9 м³ за 3 суток = 3 м³/сутки — не утечка
                [1001, '2026-11-13 23:00:00', 10000],
                [1001, '2026-11-16 23:00:00', 19000],
                // Замена прибора: показание упало — не утечка
                [1002, '2026-11-15 23:00:00', 900000],
                [1002, '2026-11-16 23:00:00', 100],
            ],
        );

        $this->assertCount(0, app(LeakDetector::class)->scanDay(Carbon::parse('2026-11-16')));
    }

    public function test_command_mails_new_leaks_and_page_resolves_them(): void
    {
        Mail::fake();
        config(['agr.leak_alert_emails' => 'a@example.com, b@example.com']);
        $object = $this->house([1001 => ['5', 1]], [
            [1001, '2026-11-15 23:00:00', 100000],
            [1001, '2026-11-16 23:00:00', 108000],
        ]);

        $this->artisan('leaks:check', ['--date' => '2026-11-16'])->assertSuccessful();
        $alert = LeakAlert::firstOrFail();
        $this->assertNotNull($alert->notified_at);

        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);
        $session = ['user_id' => $user->id, 'user' => $user];

        $this->withSession($session)->get('/lekked')->assertOk()->assertSee($object->address)->assertSee('8.000');
        $this->withSession($session)->get('/objects/' . $object->id . '/readings/1001')->assertOk()->assertSee('Võimalik leke: 8.000');
        $this->withSession($session)->get('/dashboard')->assertOk()->assertSee('Võimalikud lekked');

        $this->withSession($session)->post('/lekked/' . $alert->id . '/resolve')->assertRedirect();
        $this->assertNotNull($alert->fresh()->resolved_at);
        $this->assertSame(0, LeakAlert::open()->count());
    }
}
