<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_only_creates_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('employees', 3);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('services', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseCount('work_orders', 0);
        $this->assertDatabaseCount('business_documents', 0);
    }

    public function test_user_seeder_creates_login_users_without_duplicates(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('employees', 3);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@apollo.com.br',
            'role' => 'admin',
            'active' => true,
        ]);

        $admin = \App\Models\User::where('email', 'admin@apollo.com.br')->firstOrFail();
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_production_frontend_is_allowed_by_cors(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://km5refrigeracoes.com.br',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/auth/login');

        $response->assertHeader('Access-Control-Allow-Origin', 'https://km5refrigeracoes.com.br');
    }
}
