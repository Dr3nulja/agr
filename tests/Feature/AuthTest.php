<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_md5_password_logs_in_and_is_upgraded_to_bcrypt(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Legacy User',
            'login' => 'legacyuser',
            'pass' => md5('secret123'),
            'role' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post('/login', [
            'login' => 'legacyuser',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');

        $storedHash = DB::table('users')->where('id', $userId)->value('pass');

        $this->assertNotSame(md5('secret123'), $storedHash, 'password should have been rehashed to bcrypt');
        $this->assertTrue(str_starts_with($storedHash, '$2y$'));
    }

    public function test_upgraded_bcrypt_password_still_logs_in(): void
    {
        $user = User::create([
            'name' => 'Bcrypt User',
            'login' => 'bcryptuser',
            'pass' => 'secret123', // model mutator hashes with bcrypt
            'role' => 0,
        ]);

        $this->assertTrue(str_starts_with($user->pass, '$2y$'));

        $response = $this->post('/login', [
            'login' => 'bcryptuser',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_wrong_password_is_rejected(): void
    {
        DB::table('users')->insert([
            'name' => 'Legacy User',
            'login' => 'legacyuser2',
            'pass' => md5('secret123'),
            'role' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post('/login', [
            'login' => 'legacyuser2',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('login');
    }
}
