<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Database-driven redirects (legacy URLs from the previous site, renamed pages, cycle rollovers).
 * Also normalises public GET paths to lowercase without a trailing slash (Laravel convention).
 * Registered as GLOBAL middleware so it runs before route matching and catches unknown URLs.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        // One host: www.<domain> answers 301 to the APP_URL host with the same path (duplicate pages otherwise; the
        // certificate covers both names). Off in local development, where the host is whatever the developer uses.
        $canonicalHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        if ((! app()->environment('local', 'testing') || config('app.force_canonical_host')) && $canonicalHost !== '' && strtolower($request->getHost()) === 'www.'.$canonicalHost) {
            return redirect()->away(rtrim((string) config('app.url'), '/').$request->getRequestUri(), 301);
        }

        // The front controller must never be a public URL: /index.php and /index.php/fees duplicate / and /fees
        // (Apache and `php artisan serve` both answer them). Checked on the raw request URI because Laravel strips
        // the script name into the base URL before routing.
        if (preg_match('#^/index\.php(?=/|\?|$)#i', $request->getRequestUri())) {
            $rest = '/'.trim(strtolower((string) preg_replace('#^(/index\.php)+#i', '', strtok($request->getRequestUri(), '?'))), '/');
            $qs = $request->getQueryString();

            // Absolute URL from scheme and host only: redirect('/x') would prefix the base URL (/index.php) and loop.
            $root = app()->environment('local', 'testing') && ! config('app.force_canonical_host') ? $request->getSchemeAndHttpHost() : rtrim((string) config('app.url'), '/');

            return redirect()->away($root.$rest.($qs ? '?'.$qs : ''), 301);
        }

        $raw = $request->getPathInfo();
        $path = '/'.trim($raw, '/');

        $map = Cache::remember('redirects.map', 300, function () {
            if (! Schema::hasTable('redirects')) {
                return [];
            }

            return DB::table('redirects')->where('active', true)->pluck('to_path', 'from_path')->all();
        });

        if (isset($map[$path])) {
            return redirect($map[$path], 301);
        }

        $isFile = str_contains(basename($raw), '.');
        $skip = $raw === '/' || $isFile || preg_match('#^/(portal|admin|api|login|register|password|webhooks|storage|up|_debugbar)(/|$)#i', $raw);

        if (! $skip) {
            $canon = '/'.trim(strtolower($raw), '/');
            if ($canon !== $raw) {
                $qs = $request->getQueryString();

                return redirect($canon.($qs ? '?'.$qs : ''), 301);
            }
        }

        return $next($request);
    }
}
