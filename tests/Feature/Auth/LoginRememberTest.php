<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginRememberTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'email' => 'tri',
            'password' => Hash::make('Tri@Muakey111'),
            'remember_token' => null,
        ]);
    }

    private function recallerName(): string
    {
        return Auth::guard('web')->getRecallerName();
    }

    /** Giá trị cookie ghi nhớ mà trình duyệt gửi lên (helper test tự mã hoá giúp). */
    private function recallerValue(User $user, string $token): string
    {
        return $user->id.'|'.$token.'|'.$user->password;
    }

    public function test_login_page_shows_the_remember_checkbox(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('name="remember"', false);
    }

    public function test_remember_me_stores_a_token_and_sets_the_recaller_cookie(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/login', [
            'username' => 'tri',
            'password' => 'Tri@Muakey111',
            'remember' => '1',
        ]);

        $response->assertRedirect('/families');
        $this->assertAuthenticatedAs($user);
        $response->assertCookie($this->recallerName());

        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_without_remember_does_not_set_the_recaller_cookie(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/login', [
            'username' => 'tri',
            'password' => 'Tri@Muakey111',
        ]);

        $response->assertRedirect('/families');
        $this->assertAuthenticatedAs($user);
        $response->assertCookieMissing($this->recallerName());

        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_recaller_cookie_logs_the_user_back_in_without_a_session(): void
    {
        $user = $this->makeUser();
        $token = Str::random(60);
        $user->forceFill(['remember_token' => $token])->save();

        // Không có session, chỉ có cookie ghi nhớ như khi quay lại sau nhiều ngày.
        $response = $this->withCookie($this->recallerName(), $this->recallerValue($user, $token))
            ->get('/families');

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_stale_recaller_cookie_does_not_log_the_user_in(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        $response = $this->withCookie($this->recallerName(), $this->recallerValue($user, Str::random(60)))
            ->get('/families');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_logout_invalidates_the_remember_token(): void
    {
        $user = $this->makeUser();

        $this->post('/login', [
            'username' => 'tri',
            'password' => 'Tri@Muakey111',
            'remember' => '1',
        ]);

        $tokenBefore = $user->fresh()->remember_token;
        $this->assertNotNull($tokenBefore);

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertNotSame($tokenBefore, $user->fresh()->remember_token);
    }
}
