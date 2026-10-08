<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ClinicAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('clinic_admin')->check()) {
            return redirect()->route('clinic.admin');
        }

        return view('clinic-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'max:200'],
        ]);
        $key = 'clinic-admin-login|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Bạn thử đăng nhập quá nhiều lần. Vui lòng thử lại sau ' . RateLimiter::availableIn($key) . ' giây.',
            ]);
        }

        if (!Auth::guard('clinic_admin')->attempt($credentials, false)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không chính xác.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('clinic.admin'));
    }

    public function logout(Request $request)
    {
        Auth::guard('clinic_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('clinic.admin.login');
    }
}
