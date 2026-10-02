<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSeederTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED = [
        ['tri', 'Trí', 'Tri@Muakey111'],
        ['thao', 'Thảo', 'Thao@Muakey111'],
        ['long', 'Long', 'Long@Muakey111'],
        ['nhat', 'Nhật', 'Nhat@Muakey111'],
    ];

    public function test_seeder_creates_the_four_accounts_with_current_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (self::EXPECTED as [$username, $name, $password]) {
            $user = User::where('email', $username)->first();

            $this->assertNotNull($user, "Thiếu tài khoản {$username}");
            $this->assertSame($name, $user->name);
            $this->assertTrue(Hash::check($password, $user->password), "Sai mật khẩu tài khoản {$username}");
        }

        $this->assertSame(4, User::count());
    }

    public function test_seeder_does_not_create_the_removed_admin_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'admin']);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, User::count());
    }
}
