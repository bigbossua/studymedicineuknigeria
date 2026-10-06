<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureTwoFactor;
use App\Models\AdminAction;
use App\Models\User;
use App\Support\Seo;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public const PENDING_SECRET = 'two_factor.pending_secret';

    private const FRESH_CODES = 'two_factor.fresh_codes';

    public function setup(Request $request)
    {
        $user = $request->user();
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.challenge');
        }
        $secret = $request->session()->get(self::PENDING_SECRET);
        if (! $secret) {
            $secret = Totp::generateSecret();
            $request->session()->put(self::PENDING_SECRET, $secret);
        }

        return view('auth.two-factor-setup', [
            'seo' => Seo::make('Set up two-step verification')->noindex(),
            'secret' => Totp::pretty($secret),
            'uri' => Totp::otpauthUri($secret, $user->email, config('site.name')),
            'required' => $user->requiresTwoFactor(),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->hasTwoFactorEnabled()) {
            // Never replace an enrolled authenticator from a session-held secret: changing it needs the challenge first,
            // then disable and set up again (security audit 2026-10-04).
            return redirect()->route('two-factor.challenge');
        }
        $data = $request->validate(['code' => 'required|string|max:12']);
        $secret = $request->session()->get(self::PENDING_SECRET);
        if (! $secret || Totp::matchingCounter($secret, $data['code']) === null) {
            throw ValidationException::withMessages(['code' => 'That code did not match. Check the time on your phone and try the next code.']);
        }

        $plain = $this->makeRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $plain),
        ])->save();

        $request->session()->forget(self::PENDING_SECRET);
        $request->session()->put(EnsureTwoFactor::SESSION_KEY, $user->id);
        $request->session()->put(self::FRESH_CODES, $plain);
        if ($user->isStaff()) {
            AdminAction::log('two_factor.enabled', $user);
        }

        return redirect()->route('two-factor.recovery-codes');
    }

    public function recoveryCodes(Request $request)
    {
        $codes = $request->session()->pull(self::FRESH_CODES);
        if (! $codes) {
            return redirect()->to($this->home($request->user()));
        }

        return view('auth.two-factor-codes', ['seo' => Seo::make('Your recovery codes')->noindex(), 'codes' => $codes, 'next' => redirect()->intended($this->home($request->user()))->getTargetUrl()]);
    }

    public function challenge(Request $request)
    {
        $user = $request->user();
        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->to($this->home($user));
        }
        if ($request->session()->get(EnsureTwoFactor::SESSION_KEY) === $user->id) {
            return redirect()->intended($this->home($user));
        }

        return view('auth.two-factor-challenge', ['seo' => Seo::make('Two-step verification')->noindex()]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['code' => 'required|string|max:20']);
        $key = 'two-factor:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 8)) {
            Log::warning('auth.two_factor_locked', ['user_id' => $user->id, 'ip' => $request->ip()]);
            throw ValidationException::withMessages(['code' => 'Too many attempts. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes.']);
        }

        if (! $this->passesTotp($user, $data['code']) && ! $this->passesRecoveryCode($user, $data['code'])) {
            RateLimiter::hit($key, 900);
            Log::warning('auth.two_factor_failed', ['user_id' => $user->id, 'ip' => $request->ip()]);
            throw ValidationException::withMessages(['code' => 'That code did not match.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put(EnsureTwoFactor::SESSION_KEY, $user->id);

        return redirect()->intended($this->home($user));
    }

    /** Students may switch two-step verification off again; staff may not. */
    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->requiresTwoFactor(), 403, 'Two-step verification is mandatory for staff accounts.');
        // Only a session that has already passed the second factor may switch it off (a stolen password alone is not enough).
        abort_unless($request->session()->get(EnsureTwoFactor::SESSION_KEY) === $user->id, 403, 'Confirm your authenticator code first.');
        $request->validate(['current_password' => 'required|current_password']);
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
        $request->session()->forget(EnsureTwoFactor::SESSION_KEY);

        return redirect()->route('portal.profile')->with('status', 'Two-step verification has been switched off.');
    }

    private function passesTotp(User $user, string $code): bool
    {
        $counter = Totp::matchingCounter($user->two_factor_secret, $code);
        if ($counter === null) {
            return false;
        }

        // A code may be used once only, even inside the ±30 s acceptance window.
        return Cache::add("totp-used:{$user->id}:{$counter}", 1, Totp::PERIOD * 4);
    }

    private function passesRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper(trim($code));
        $stored = $user->two_factor_recovery_codes ?? [];
        foreach ($stored as $i => $hash) {
            if (Hash::check($code, $hash)) {
                unset($stored[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($stored)])->save();

                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function makeRecoveryCodes(int $count = 8): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = '';
            for ($j = 0; $j < 10; $j++) {
                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5);
        }

        return $codes;
    }

    private function home(User $user): string
    {
        return $user->isStaff() ? route('admin.dashboard') : route('portal.dashboard');
    }
}
