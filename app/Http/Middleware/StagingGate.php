<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP basic authentication in front of the whole staging site (docs/architecture/21.6), so
 * search engines, students and curious visitors never see pre-release content. Active only when
 * APP_ENV=staging and both STAGING_BASIC_USER and STAGING_BASIC_PASSWORD are set. The health
 * endpoint and Stripe webhooks stay open so monitoring and test payments keep working.
 */
class StagingGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('site.staging_basic_user');
        $pass = (string) config('site.staging_basic_password');
        if (! app()->environment('staging') || $user === '' || $pass === '' || $request->is('up', 'webhooks/*')) {
            return $next($request);
        }
        if (hash_equals($user, (string) $request->getUser()) && hash_equals($pass, (string) $request->getPassword())) {
            return $next($request);
        }

        return response('Staging site: authentication required.', 401, ['WWW-Authenticate' => 'Basic realm="SMUKN staging", charset="UTF-8"', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
