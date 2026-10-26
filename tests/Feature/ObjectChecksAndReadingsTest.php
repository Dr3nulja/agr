<?php

namespace Tests\Feature;

use App\Mail\ObjectReportMail;
use App\Models\AgrObject;
use App\Models\SimCard;
use App\Models\User;
use App\Services\ReadingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesLegacyObjectTables;
use Tests\TestCase;

class ObjectChecksAndReadingsTest extends TestCase
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

    private function admin(): array
    {
        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);

        return ['user_id' => $user->id, 'user' => $user];
    }

    /**
     * Объект с таблицами приборов. $devices — [devid => [квартира, devtype]]
     */
    private function object(array $devices = [], array $attributes = []): AgrObject
    {
        $object = AgrObject::create($attributes + [
            'address' => 'Narva mnt ' . mt_rand(1, 999),
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

        return $object;
    }

    public function test_house_with_water_and_heat_is_checked_in_two_steps(): void
    {
        $session = $this->admin();
        $object = $this->object([1001 => ['1', 1], 2001 => ['1', 3]]);

        $this->withSession($session)->post('/objects/' . $object->id . '/check')->assertRedirect('/objects/' . $object->id);
        $object->refresh();
        $this->assertSame(AgrObject::CHECK_WATER, $object->checkState());
        $this->assertNotNull($object->water_checked_at);
        $this->assertNull($object->selDate);

        $this->withSession($session)->get('/objects/' . $object->id)->assertOk()
            ->assertSee('Vesi esitatud')->assertSee('Märgi esitatuks: soojus (kõik)');
        $this->withSession($session)->get('/objects?checked=water')->assertSee($object->address);
        $this->withSession($session)->get('/objects?checked=unchecked')->assertDontSee($object->address);

        $this->withSession($session)->post('/objects/' . $object->id . '/check');
        $object->refresh();
        $this->assertSame(AgrObject::CHECK_ALL, $object->checkState());
        $this->assertNotNull($object->heat_checked_at);
        $this->assertNotNull($object->selDate);

        $this->withSession($session)->post('/objects/' . $object->id . '/uncheck');
        $object->refresh();
        $this->assertSame(AgrObject::CHECK_NONE, $object->checkState());
        $this->assertNull($object->water_checked_at);
        $this->assertNull($object->heat_checked_at);
    }

    public function test_house_with_only_water_is_fully_checked_at_once(): void
    {
        $object = $this->object([1001 => ['1', 1], 1002 => ['1', 2]]);

        $this->withSession($this->admin())->post('/objects/' . $object->id . '/check');

        $object->refresh();
        $this->assertSame(AgrObject::CHECK_ALL, $object->checkState());
        $this->assertNotNull($object->water_checked_at);
        $this->assertNull($object->heat_checked_at);
    }

    public function test_send_report_mails_step_report_to_all_addresses_and_checks(): void
    {
        Mail::fake();
        $session = $this->admin();
        $object = $this->object([1001 => ['1', 1], 2001 => ['1', 3]], ['report_emails' => 'a@example.ee, b@example.ee']);

        $this->withSession($session)->post('/objects/' . $object->id . '/send-report')->assertRedirect('/objects/' . $object->id);

        Mail::assertSent(ObjectReportMail::class, fn ($mail) => $mail->hasTo('a@example.ee') && $mail->hasTo('b@example.ee')
            && $mail->reportTitle === 'Veemõõtjate näidud');
        $this->assertSame(AgrObject::CHECK_WATER, $object->fresh()->checkState());

        $this->withSession($session)->post('/objects/' . $object->id . '/send-report');

        Mail::assertSent(ObjectReportMail::class, fn ($mail) => $mail->reportTitle === 'Jaoturite näidud');
        $this->assertSame(AgrObject::CHECK_ALL, $object->fresh()->checkState());
    }

    public function test_send_report_without_email_does_not_check(): void
    {
        Mail::fake();
        $object = $this->object([1001 => ['1', 1]]);

        $this->withSession($this->admin())->post('/objects/' . $object->id . '/send-report')->assertSessionHas('error');

        Mail::assertNothingSent();
        $this->assertSame(AgrObject::CHECK_NONE, $object->fresh()->checkState());
    }

    public function test_report_emails_are_validated_and_normalized(): void
    {
        $session = $this->admin();
        $object = $this->object();
        $form = ['address' => $object->address, 'City' => 'Tallinn', 'IMEI' => $object->IMEI, 'Devqtty' => 1];

        $this->withSession($session)->put('/objects/' . $object->id, $form + ['report_emails' => 'a@example.ee, not-an-email'])
            ->assertSessionHasErrors('report_emails');

        $this->withSession($session)->put('/objects/' . $object->id, $form + ['report_emails' => 'a@example.ee;b@example.ee ,  A@example.ee']);
        $this->assertSame('a@example.ee, b@example.ee', $object->fresh()->report_emails);
    }

    public function test_objects_list_filters_by_several_managers_and_merges_agr_with_prem(): void
    {
        $session = $this->admin();
        $kat = $this->object([], ['address' => 'KAT maja', 'manager' => 2, 'Company' => 2]);
        $reg = $this->object([], ['address' => 'REG maja', 'manager' => 3, 'Company' => 1]);
        $nat = $this->object([], ['address' => 'NAT maja', 'manager' => 4, 'Company' => 1]);

        $this->withSession($session)->get('/objects?manager[]=2&manager[]=3')
            ->assertSee('KAT maja')->assertSee('REG maja')->assertDontSee('NAT maja')
            ->assertDontSee('Prem');

        $this->withSession($session)->get('/objects?company=agr')
            ->assertSee('KAT maja')->assertSee('REG maja')->assertSee('NAT maja');
    }

    public function test_readings_page_shows_monthly_consumption_and_exports(): void
    {
        $session = $this->admin();
        $object = $this->object([1001 => ['5', 1]]);
        DB::table('mlog_' . $object->id)->insert([
            ['devid' => 1001, 'date' => '2026-07-31 23:00:00', 'value' => 10000],
            ['devid' => 1001, 'date' => '2026-08-15 12:00:00', 'value' => 11000],
            ['devid' => 1001, 'date' => '2026-08-31 23:00:00', 'value' => 12500],
            ['devid' => 1001, 'date' => '2026-09-30 23:00:00', 'value' => 13000],
        ]);
        DB::table('lastdata_' . $object->id)->insert(['devid' => 1001, 'date' => '2026-09-30 23:00:00', 'value' => 13000, 'mvalue' => 13000, 'prevVal' => 12500]);

        $service = app(ReadingsService::class);
        $devices = $service->devices($object->id);
        $monthly = $service->monthly($object->id, $devices, '2026-08', '2026-09');
        $this->assertEqualsWithDelta(2.5, $monthly[1001]['2026-08']['consumption'], 0.0001);
        $this->assertEqualsWithDelta(0.5, $monthly[1001]['2026-09']['consumption'], 0.0001);

        $this->withSession($session)->get('/objects/' . $object->id . '/readings?from=2026-08&to=2026-09&location=5')
            ->assertOk()->assertSee('2.500')->assertSee('<svg', false);
        $this->withSession($session)->get('/objects/' . $object->id . '/readings/print?from=2026-08&to=2026-09&location=5')
            ->assertOk()->assertSee('Korter:')->assertSee($object->address);
        $this->withSession($session)->get('/objects/' . $object->id . '/readings/export.xlsx?from=2026-08&to=2026-09')
            ->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_manual_reading_updates_current_value_and_is_logged(): void
    {
        $session = $this->admin();
        $object = $this->object([1001 => ['5', 1]]);
        DB::table('lastdata_' . $object->id)->insert(['devid' => 1001, 'date' => '2026-09-01 00:00:00', 'value' => 13000, 'mvalue' => 13000, 'prevVal' => 12000]);

        $this->withSession($session)->post('/objects/' . $object->id . '/readings/1001/history', ['date' => '2026-10-01T08:00', 'value' => '14.25'])
            ->assertSessionHasNoErrors();

        $this->assertSame(14250, (int) DB::table('mlog_' . $object->id)->where('devid', 1001)->value('value'));
        $current = DB::table('lastdata_' . $object->id)->where('devid', 1001)->first();
        $this->assertSame(14250, (int) $current->value);
        $this->assertSame('2026-10-01 08:00:00', $current->date);
        $this->assertSame(2, DB::table('reading_corrections')->where('object_id', $object->id)->count());

        $rowId = DB::table('mlog_' . $object->id)->value('id');
        $this->withSession($session)->put('/objects/' . $object->id . '/readings/1001/history/' . $rowId, ['date' => '2026-10-02T08:00', 'value' => '14.5']);
        $this->assertSame(14500, (int) DB::table('mlog_' . $object->id)->where('id', $rowId)->value('value'));

        $this->withSession($session)->put('/objects/' . $object->id . '/readings/1001/current', [
            'date' => '2026-10-02T08:00', 'value' => '14.5', 'mvalue' => '14', 'prev_value' => '13',
        ]);
        $current = DB::table('lastdata_' . $object->id)->where('devid', 1001)->first();
        $this->assertSame(14000, (int) $current->mvalue);
        $this->assertSame(13000, (int) $current->prevVal);

        $this->withSession($session)->get('/objects/' . $object->id . '/readings/1001')->assertOk()->assertSee('käsitsi');

        $this->withSession($session)->delete('/objects/' . $object->id . '/readings/1001/history/' . $rowId);
        $this->assertSame(0, DB::table('mlog_' . $object->id)->count());
    }

    public function test_fixed_month_keeps_snapshot_after_later_changes(): void
    {
        $session = $this->admin();
        $object = $this->object([1001 => ['5', 1]]);
        DB::table('lastdata_' . $object->id)->insert(['devid' => 1001, 'date' => '2026-09-30 00:00:00', 'value' => 13000, 'mvalue' => 13000, 'prevVal' => 12000]);

        $this->withSession($session)->post('/objects/' . $object->id . '/readings/fix-month', ['period' => '2026-09'])->assertSessionHasNoErrors();
        DB::table('lastdata_' . $object->id)->where('devid', 1001)->update(['value' => 99000]);

        $snapshot = app(ReadingsService::class)->fixedDevices($object->id, '2026-09');
        $this->assertCount(1, $snapshot);
        $this->assertEqualsWithDelta(13.0, $snapshot[0]['value'], 0.0001);

        $this->withSession($session)->get('/objects/' . $object->id . '/readings?snapshot=2026-09')->assertOk()->assertSee('13.000');
    }

    public function test_sim_number_is_single_and_unique_in_any_format(): void
    {
        $session = $this->admin();
        $sim = new SimCard(['number' => '+37259123456']);
        $sim->status = SimCard::STATUS_FREE;
        $sim->save();

        $this->withSession($session)->post('/sims', ['number' => '5912 3456'])->assertSessionHasErrors('number');
        $this->withSession($session)->post('/sims', ['number' => '0037259123456'])->assertSessionHasErrors('number');
        $this->withSession($session)->post('/sims', ['number' => '59111111, 59222222'])->assertSessionHasErrors('number');
        $this->withSession($session)->post('/sims', ['number' => '+372 5933 3333'])->assertSessionHasNoErrors();
        $this->assertSame('+37259333333', SimCard::latest('id')->value('number'));

        $this->withSession($session)->post('/sims/bulk', ['lines' => "59123456\n59444444"]);
        $this->assertSame(3, SimCard::count());
    }

    public function test_sim_assigned_from_register_records_installer(): void
    {
        $object = $this->object();
        $sim = new SimCard(['number' => '+37259555555']);
        $sim->status = SimCard::STATUS_FREE;
        $sim->save();

        $this->withSession($this->admin())->post('/sims/' . $sim->id . '/assign', ['object_id' => $object->id, 'slot' => 1, 'installer' => 'Jaan']);

        $this->assertSame('Jaan', $sim->fresh()->issued_to);
        $this->assertSame(SimCard::STATUS_ASSIGNED, $sim->fresh()->status);
    }
}
