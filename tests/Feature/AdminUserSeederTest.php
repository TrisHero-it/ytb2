<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_user_with_current_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin')->first();

        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('Muakey@@111', $admin->password));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('email', 'admin')->count());
    }
}
