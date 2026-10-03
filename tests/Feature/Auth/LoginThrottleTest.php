<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Trước đây đoán mật khẩu bao nhiêu lần cũng được, không có gì chặn lại.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:admin|127.0.0.1');
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'email' => 'admin',
            'password' => Hash::make('Muakey@@111'),
        ]);
    }

    private function attempt(string $password)
    {
        return $this->post('/login', ['username' => 'admin', 'password' => $password]);
    }

    public function test_locks_the_account_out_after_five_wrong_passwords(): void
    {
        $this->makeAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('wrong-password');
        }

        $response = $this->attempt('wrong-password');

        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString(
            'nhập sai quá nhiều lần',
            session('errors')->first('username'),
        );
        $this->assertGuest();
    }

    public function test_the_right_password_is_refused_while_locked_out(): void
    {
        $this->makeAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('wrong-password');
        }

        $response = $this->attempt('Muakey@@111');

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_a_few_wrong_tries_still_show_the_normal_message(): void
    {
        $this->makeAdmin();

        $response = $this->attempt('wrong-password');

        $response->assertSessionHasErrors('username');
        $this->assertSame(
            'Tài khoản hoặc mật khẩu không đúng',
            session('errors')->first('username'),
        );
    }

    public function test_logging_in_successfully_clears_the_counter(): void
    {
        $this->makeAdmin();

        $this->attempt('wrong-password');
        $this->attempt('wrong-password');
        $this->attempt('Muakey@@111');

        $this->assertAuthenticated();
        $this->assertSame(0, RateLimiter::attempts('login:admin|127.0.0.1'));
    }
}
