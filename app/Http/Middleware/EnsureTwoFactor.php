<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff and admin accounts must have an authenticator app enrolled, and every
 * account with two-factor enabled must pass the challenge once per session.
 */
class EnsureTwoFactor
{
    public const SESSION_KEY = 'two_factor.passed_for';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->hasTwoFactorEnabled()) {
            if ($request->session()->get(self::SESSION_KEY) === $user->id) {
                return $next($request);
            }
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('two-factor.challenge');
        }

        if ($user->requiresTwoFactor()) {
            return redirect()->route('two-factor.setup')->with('status', 'Staff accounts must set up an authenticator app before using the admin area.');
        }

        return $next($request);
    }
}
