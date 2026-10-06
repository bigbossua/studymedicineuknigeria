<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureTwoFactor;
use App\Models\Lead;
use App\Models\User;
use App\Support\Funnel;
use App\Support\Seo;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login', ['seo' => Seo::make('Student portal login')->noindex()]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string', 'remember' => 'nullable|boolean']);
        $key = 'login:'.Str::lower($data['email']).'|'.$request->ip();
        // A second limit on the account alone: credential stuffing from many addresses against one account.
        $accountKey = 'login-account:'.Str::lower($data['email']);
        foreach ([[$key, 10], [$accountKey, 50]] as [$k, $max]) {
            if (RateLimiter::tooManyAttempts($k, $max)) {
                Log::warning('auth.locked', ['account' => self::accountRef($data['email']), 'ip' => $request->ip(), 'limit' => $k === $key ? 'account+address' : 'account']);
                throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.ceil(RateLimiter::availableIn($k) / 60).' minutes.']);
            }
        }
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], (bool) ($data['remember'] ?? false))) {
            RateLimiter::hit($key, 900);
            RateLimiter::hit($accountKey, 3600);
            // security log: a keyed reference to the account, never the address or the password
            Log::warning('auth.login_failed', ['account' => self::accountRef($data['email']), 'ip' => $request->ip()]);
            throw ValidationException::withMessages(['email' => 'These details do not match our records.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->forget(TwoFactorController::PENDING_SECRET); // a secret planted before sign-in never survives it
        $request->session()->forget(EnsureTwoFactor::SESSION_KEY); // every sign-in repeats the authenticator step
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
        if (! config('site.registration_open')) {
            return view('auth.register-closed', ['seo' => Seo::make('Registration opens shortly')->noindex()]);
        }

        return view('auth.register', ['seo' => Seo::make('Create your account')->noindex(), 'lead' => $request->session()->get('lead')]);
    }

    public function register(Request $request): RedirectResponse
    {
        abort_unless(config('site.registration_open'), 503, 'Registration opens shortly.');
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
        Funnel::track('account_created', [], null, $user->id);
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

    public function showForgot()
    {
        return view('auth.forgot', ['seo' => Seo::make('Reset your password')->noindex()]);
    }

    public function sendReset(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        // Same message whether or not the account exists (no enumeration)
        return back()->with('status', 'If an account exists for that email, a reset link has been sent.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset', ['seo' => Seo::make('Choose a new password')->noindex(), 'token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', 'Your password has been changed. You can sign in now.')
            // An unknown email gets the same message as a bad token, so this form never confirms that an account exists.
            : back()->withErrors(['email' => __($status === Password::InvalidUser ? Password::InvalidToken : $status)]);
    }

    /** A short keyed reference that lets staff group failures for one account without logging the email address. */
    private static function accountRef(string $email): string
    {
        return substr(hash_hmac('sha256', Str::lower($email), (string) config('app.key')), 0, 16);
    }
}
