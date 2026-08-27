<?php

namespace Tests\Feature;

use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test: the dashboard used to order logs by a column
     * (`ins_date`) that doesn't exist on this app's `log` table, crashing
     * the page with a 500 for every logged-in user.
     */
    public function test_dashboard_loads_with_recent_logs(): void
    {
        $user = User::create(['name' => 'Admin', 'login' => 'admin', 'pass' => 'secret', 'role' => 1]);
        Log::create(['Content' => 'Something happened']);

        $response = $this->withSession(['user_id' => $user->id, 'user' => $user])->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Something happened');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
