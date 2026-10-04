<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Outside local development every generated URL (password-reset and email-verification links, redirects, emails) is
 * built from APP_URL, never from the request's Host header. Without this a request sent with a forged Host makes the
 * application email a real reset token on a link to the attacker's domain. TrustHosts (bootstrap/app.php) refuses
 * foreign hosts as a first line; this is the second, and it also covers queued mail built outside a request.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('local', 'testing') || config('app.force_canonical_host')) {
            URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        return $next($request);
    }
}
