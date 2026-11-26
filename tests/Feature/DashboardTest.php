<?php

namespace Tests\Feature;

use App\Models\AgrObject;
use App\Models\ObjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Главная показывает последние задачи для мобильного приложения (раньше — логи).
     */
    public function test_dashboard_loads_with_recent_tasks(): void
    {
        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);
        $object = AgrObject::create(['address' => 'Testi tn 1', 'City' => 'Tallinn', 'status' => 1]);
        ObjectTask::create(['object_id' => $object->id, 'device_type' => 'water_meter', 'action_type' => 'replace']);

        $response = $this->withSession(['user_id' => $user->id, 'user' => $user])->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Testi tn 1');
    }

    public function test_missing_object_shows_estonian_404_page(): void
    {
        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);

        $this->withSession(['user_id' => $user->id, 'user' => $user])->get('/objects/999999')
            ->assertNotFound()
            ->assertSee('Lehte ei leitud')
            ->assertSee('Avalehele');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
