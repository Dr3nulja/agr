<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Models\SimCard;
use App\Models\User;
use App\Services\SimCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SimCardTest extends TestCase
{
    use RefreshDatabase;

    private function object(string $imei, array $attributes = []): AgrObject
    {
        return AgrObject::create($attributes + [
            'address' => 'Street ' . $imei,
            'City' => 'Tallinn',
            'IMEI' => $imei,
            'dtype' => 1,
            'status' => 1,
        ]);
    }

    private function sim(string $number, array $attributes = []): SimCard
    {
        $sim = new SimCard($attributes + ['number' => $number]);
        $sim->status = SimCard::STATUS_FREE;
        $sim->save();

        return $sim;
    }

    private function admin(): array
    {
        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);

        return ['user_id' => $user->id, 'user' => $user];
    }

    public function test_migration_moves_existing_object_sims_into_register(): void
    {
        $migration = require database_path('migrations/2026_10_19_000000_create_sim_cards_table.php');
        $migration->down();

        $first = $this->object('100000000000001', ['GSMNR' => '+3725001', 'GSMSERIAL' => 'ICC1', 'pin1' => '1111', 'puk1' => '22222222', 'GSMNR2' => '+3725002']);
        $duplicate = $this->object('100000000000002', ['GSMNR' => '+3725001']);
        $this->object('100000000000003', ['GSMNR' => '', 'GSMSERIAL' => '']);

        $migration->up();

        $this->assertSame(2, SimCard::count());
        $slot1 = SimCard::where('object_id', $first->id)->where('slot', 1)->first();
        $this->assertSame('+3725001', $slot1->number);
        $this->assertSame('ICC1', $slot1->iccid);
        $this->assertSame('1111', $slot1->pin1);
        $this->assertSame(SimCard::STATUS_ASSIGNED, $slot1->status);
        $this->assertSame('+3725002', SimCard::where('object_id', $first->id)->where('slot', 2)->value('number'));
        $this->assertFalse(SimCard::where('object_id', $duplicate->id)->exists());
        $this->assertStringContainsString('#' . $duplicate->id, $slot1->comment);
    }

    public function test_migration_gives_duplicate_sim_to_object_that_reported_last(): void
    {
        $migration = require database_path('migrations/2026_10_19_000000_create_sim_cards_table.php');
        $migration->down();

        $stale = $this->object('110000000000001', ['GSMNR' => "\u{202A}+3725009\u{202C}", 'lastSession' => now()->subMonth()]);
        $current = $this->object('110000000000002', ['GSMNR' => '+3725009', 'lastSession' => now()]);

        $migration->up();

        $sim = SimCard::sole();
        $this->assertSame('+3725009', $sim->number); // без невидимых символов из копипаста
        $this->assertSame($current->id, $sim->object_id);
        $this->assertSame(SimCard::CONFLICT_NOTE . ' #' . $stale->id, $sim->comment);

        $this->withSession($this->admin())->get('/sims')->assertOk()->assertSee('Kontrollida');
    }

    public function test_assigned_sim_can_only_be_released_not_moved(): void
    {
        $service = app(SimCardService::class);
        $objectA = $this->object('200000000000001');
        $objectB = $this->object('200000000000002');
        $sim = $this->sim('+3725100', ['iccid' => 'ICC100', 'pin1' => '1234']);

        $service->issue($sim, 'Jaan');
        $this->assertSame(SimCard::STATUS_ISSUED, $sim->fresh()->status);
        $this->assertSame('Jaan', $sim->fresh()->issued_to);

        $service->assign($sim, $objectA, 1);
        $sim->refresh();
        $this->assertSame(SimCard::STATUS_ASSIGNED, $sim->status);
        $this->assertSame('Jaan', $sim->issued_to); // кто поставил — остаётся видно
        $this->assertSame('+3725100', $objectA->fresh()->GSMNR);
        $this->assertSame('ICC100', $objectA->fresh()->GSMSERIAL);
        $this->assertSame('1234', $objectA->fresh()->pin1);

        try {
            $service->assign($sim, $objectB, 1);
            $this->fail('Assigned SIM was moved to another object');
        } catch (ValidationException) {
        }

        try {
            $service->issue($sim, 'Mari');
            $this->fail('Assigned SIM was issued to a technician');
        } catch (ValidationException) {
        }

        $service->release($sim);
        $this->assertSame(SimCard::STATUS_FREE, $sim->fresh()->status);
        $this->assertNull($sim->fresh()->object_id);
        $this->assertSame('', $objectA->fresh()->GSMNR);
    }

    public function test_assign_refuses_occupied_slot(): void
    {
        $service = app(SimCardService::class);
        $object = $this->object('300000000000001');
        $service->assign($this->sim('+3725201'), $object, 1);

        $this->expectException(ValidationException::class);
        $service->assign($this->sim('+3725202'), $object, 1);
    }

    public function test_object_form_replaces_and_swaps_sims(): void
    {
        $session = $this->admin();
        $object = $this->object('400000000000001');
        $old = $this->sim('+3725301');
        $new = $this->sim('+3725302');
        app(SimCardService::class)->assign($old, $object, 1);

        $form = ['address' => $object->address, 'City' => 'Tallinn', 'IMEI' => $object->IMEI, 'Devqtty' => 1];

        // Замена: старая карта уходит в свободные, новая встаёт в слот
        $this->withSession($session)->put('/objects/' . $object->id, $form + ['sim1_id' => $new->id, 'sim2_id' => ''])
            ->assertRedirect('/objects/' . $object->id);
        $this->assertSame(SimCard::STATUS_FREE, $old->fresh()->status);
        $this->assertSame(1, $new->fresh()->slot);
        $this->assertSame('+3725302', $object->fresh()->GSMNR);

        // Перестановка между слотами одного объекта
        $this->withSession($session)->put('/objects/' . $object->id, $form + ['sim1_id' => $old->id, 'sim2_id' => $new->id])
            ->assertSessionHasNoErrors();
        $this->withSession($session)->put('/objects/' . $object->id, $form + ['sim1_id' => $new->id, 'sim2_id' => $old->id])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $new->fresh()->slot);
        $this->assertSame(2, $old->fresh()->slot);
        $this->assertSame('+3725302', $object->fresh()->GSMNR);
        $this->assertSame('+3725301', $object->fresh()->GSMNR2);
    }

    public function test_object_form_cannot_take_sim_from_another_object(): void
    {
        $session = $this->admin();
        $owner = $this->object('500000000000001');
        $other = $this->object('500000000000002');
        $sim = $this->sim('+3725401');
        app(SimCardService::class)->assign($sim, $owner, 1);

        $this->withSession($session)
            ->put('/objects/' . $other->id, ['address' => 'X', 'City' => 'Y', 'IMEI' => $other->IMEI, 'Devqtty' => 1, 'sim1_id' => $sim->id])
            ->assertSessionHasErrors('sim1_id');

        $this->assertSame($owner->id, $sim->fresh()->object_id);
    }

    public function test_deleting_object_frees_its_sims(): void
    {
        $object = $this->object('600000000000001');
        $sim = $this->sim('+3725501');
        app(SimCardService::class)->assign($sim, $object, 2);

        $this->withSession($this->admin())->delete('/objects/' . $object->id);

        $this->assertSame(SimCard::STATUS_FREE, $sim->fresh()->status);
    }

    public function test_register_page_and_admin_actions(): void
    {
        $session = $this->admin();
        $object = $this->object('700000000000001');

        $this->withSession($session)->post('/sims', ['number' => '+3725601', 'iccid' => 'ICC601', 'pin1' => '0000'])->assertSessionHasNoErrors();
        $this->withSession($session)->post('/sims', ['number' => '+3725601'])->assertSessionHasErrors('number');
        $this->withSession($session)->post('/sims/bulk', ['lines' => "+3725602;ICC602;1111;22222222\n+3725601;ICC601\n\n+3725603"])->assertSessionHasNoErrors();
        $this->assertSame(3, SimCard::count());

        $sim = SimCard::where('number', '+3725602')->first();
        $this->assertSame('22222222', $sim->puk1);

        $this->withSession($session)->post('/sims/' . $sim->id . '/issue', ['issued_to' => 'Jaan Tamm'])->assertSessionHasNoErrors();
        $this->withSession($session)->post('/sims/' . $sim->id . '/assign', ['object_id' => $object->id, 'slot' => 1])->assertSessionHasNoErrors();
        $this->assertSame('+3725602', $object->fresh()->GSMNR);

        $this->withSession($session)->delete('/sims/' . $sim->id)->assertSessionHasErrors('sim');
        $this->assertTrue(SimCard::whereKey($sim->id)->exists());

        $this->withSession($session)->get('/sims?status=assigned')
            ->assertOk()
            ->assertSee('+3725602')
            ->assertSee($object->address)
            ->assertDontSee('+3725603');

        $this->withSession($session)->get('/objects/' . $object->id . '/edit')->assertOk()->assertSee('+3725602');
    }

    public function test_non_admin_sees_register_without_pins_and_cannot_change_it(): void
    {
        $user = User::create(['name' => 'User', 'login' => 'user', 'pass' => 'secret', 'role' => 0]);
        $session = ['user_id' => $user->id, 'user' => $user];
        $this->sim('+3725701', ['pin1' => '9876']);

        $this->withSession($session)->get('/sims')->assertOk()->assertSee('+3725701')->assertDontSee('9876');
        $this->withSession($session)->post('/sims', ['number' => '+3725702'])->assertRedirect(route('objects.index'));
        $this->assertSame(1, SimCard::count());
    }

    public function test_sim_routes_redirect_guests_to_login(): void
    {
        $this->get('/sims')->assertRedirect('/login');
        $this->post('/sims')->assertRedirect('/login');
        $this->assertSame(0, DB::table('sim_cards')->count());
    }
}
