<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceAndObjectListTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): array
    {
        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);

        return ['user_id' => $user->id, 'user' => $user];
    }

    public function test_app_sends_maintenance_and_resend_does_not_duplicate(): void
    {
        $payload = [
            'kind' => 'heat',
            'address' => '  Narva mnt 5, Tallinn ',
            'comment' => 'Radiaator lekib',
            'installer' => 'Jaan Tamm',
            'client_uuid' => 'c0ffee00-0000-4000-8000-0000000000aa',
        ];

        $first = $this->post('/insert_hooldus', $payload)->assertOk()->json();
        $this->assertSame('ok', $first['status']);
        $retry = $this->post('/insert_hooldus', $payload)->assertOk()->json();
        $this->assertSame($first['id'], $retry['id']);

        $this->assertSame(1, MaintenanceRequest::count());
        $item = MaintenanceRequest::first();
        $this->assertSame('Narva mnt 5, Tallinn', $item->address);
        $this->assertSame('Soojus', $item->kindLabel());
    }

    public function test_maintenance_requires_kind_and_address(): void
    {
        $this->post('/insert_hooldus', ['kind' => 'gas', 'address' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.kind.0', 'Vali vesi või soojus')
            ->assertJsonPath('errors.address.0', 'Sisesta aadress');
    }

    public function test_dashboard_lists_and_closes_maintenance(): void
    {
        $this->get('/hooldus')->assertRedirect('/login');

        $session = $this->admin();
        $item = MaintenanceRequest::create(['kind' => 'water', 'address' => 'Pärnu mnt 1']);

        $this->withSession($session)->get('/hooldus')->assertOk()->assertSee('Pärnu mnt 1');

        $this->withSession($session)->post('/hooldus/' . $item->id . '/done')->assertRedirect();
        $this->assertNotNull($item->fresh()->done_at);

        // Tehtud kirje kaob avatud nimekirjast
        $this->withSession($session)->get('/hooldus')->assertOk()->assertDontSee('Pärnu mnt 1');
        $this->withSession($session)->get('/hooldus?state=done')->assertOk()->assertSee('Pärnu mnt 1');
    }

    public function test_object_list_loads_next_rows_on_scroll(): void
    {
        for ($i = 1; $i <= 31; $i++) {
            AgrObject::create([
                'address' => sprintf('Tänav %02d', $i),
                'City' => 'Tallinn',
                'IMEI' => (string) (930000000000000 + $i),
                'dtype' => 1,
                'status' => 1,
            ]);
        }
        $session = $this->admin();

        $page = $this->withSession($session)->get('/objects')->assertOk();
        $page->assertSee('Tänav 30')->assertDontSee('Tänav 31')->assertSee('id="loadMore"', false);

        $next = $this->withSession($session)
            ->getJson('/objects?page=2', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->json();
        $this->assertStringContainsString('Tänav 31', $next['html']);
        $this->assertNull($next['next']);
    }
}
