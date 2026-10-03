<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin() { return view('auth.login', ['seo' => Seo::make('Student portal login')->noindex()]); }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'nullable|boolean']);
        $key = 'login:'.Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes.']);
        }
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], (bool) ($data['remember'] ?? false))) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages(['email' => 'These details do not match our records.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->intended($request->user()->isStaff() ? route('admin.dashboard') : route('portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', ['seo' => Seo::make('Create your account')->noindex(), 'lead' => $request->session()->get('lead')]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:32',
            'whatsapp' => 'nullable|string|max:32',
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
            'terms' => 'required|accepted',
        ]);
        $user = User::create($data + ['country' => 'NG']);
        Lead::where('email', Str::lower($data['email']))->whereNull('user_id')->update(['user_id' => $user->id, 'status' => 'converted']);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function verificationNotice(Request $request)
    {
        return $request->user()->hasVerifiedEmail() ? redirect()->route('portal.dashboard') : view('auth.verify', ['seo' => Seo::make('Verify your email')->noindex()]);
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'We have sent a new verification link to '.$request->user()->email.'.');
    }

    public function showForgot() { return view('auth.forgot', ['seo' => Seo::make('Reset your password')->noindex()]); }

    public function sendReset(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        // Same message whether or not the account exists (no enumeration)
        return back()->with('status', 'If an account exists for that email, a reset link has been sent.');
    }

    public function showReset(Request $request, string $token) { return view('auth.reset', ['seo' => Seo::make('Choose a new password')->noindex(), 'token' => $token, 'email' => $request->query('email')]); }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', 'Your password has been changed. You can sign in now.')
            : back()->withErrors(['email' => __($status)]);
    }
}
