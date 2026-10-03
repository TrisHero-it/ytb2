<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Số lần nhập sai được phép trước khi khoá. */
    private const MAX_ATTEMPTS = 5;

    /** Thời gian khoá sau khi vượt quá số lần trên, tính bằng giây. */
    private const LOCKOUT_SECONDS = 60;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $throttleKey = $this->throttleKey($request, $credentials['username']);

        // Không có chốt chặn thì có thể dò mật khẩu không giới hạn.
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return $this->lockedOut($throttleKey);
        }

        $remember = $request->boolean('remember');

        if (! Auth::attempt(['email' => $credentials['username'], 'password' => $credentials['password']], $remember)) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
                return $this->lockedOut($throttleKey);
            }

            return back()->withErrors([
                'username' => 'Tài khoản hoặc mật khẩu không đúng',
            ])->onlyInput('username', 'remember');
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return redirect()->intended(route('families.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Khoá theo từng cặp tài khoản + IP để một người bị khoá không chặn người khác. */
    private function throttleKey(Request $request, string $username): string
    {
        return 'login:'.Str::lower($username).'|'.$request->ip();
    }

    private function lockedOut(string $throttleKey): RedirectResponse
    {
        $seconds = RateLimiter::availableIn($throttleKey);

        return back()->withErrors([
            'username' => "Bạn đã nhập sai quá nhiều lần. Thử lại sau {$seconds} giây.",
        ])->onlyInput('username', 'remember');
    }
}
