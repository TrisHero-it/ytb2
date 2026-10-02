<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Tài khoản đăng nhập. Cột `email` chính là username dùng để đăng nhập.
     *
     * @var array<int, array{email: string, name: string, password: string}>
     */
    private const ACCOUNTS = [
        ['email' => 'tri', 'name' => 'Trí', 'password' => 'Tri@Muakey111'],
        ['email' => 'thao', 'name' => 'Thảo', 'password' => 'Thao@Muakey111'],
        ['email' => 'long', 'name' => 'Long', 'password' => 'Long@Muakey111'],
        ['email' => 'nhat', 'name' => 'Nhật', 'password' => 'Nhat@Muakey111'],
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => Hash::make($account['password'])]
            );
        }
    }
}
