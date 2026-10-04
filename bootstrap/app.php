<?php

use App\Http\Middleware\CanonicalHost;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\EnsureTwoFactor;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StagingGate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleRedirects::class);
        $middleware->prepend(CanonicalHost::class); // URLs come from APP_URL, never the Host header (security audit 2026-10-04)
        $middleware->trustHosts(at: fn () => [parse_url((string) config('app.url'), PHP_URL_HOST)], subdomains: false);
        $middleware->prepend(StagingGate::class); // runs first: nothing on staging is served without credentials
        $middleware->append(SecurityHeaders::class);
        $middleware->alias(['staff' => EnsureStaff::class, '2fa' => EnsureTwoFactor::class]);
        $middleware->validateCsrfTokens(except: ['webhooks/stripe']);
        $middleware->encryptCookies(except: ['smukn_consent']); // plain value so the layout can decide whether to show the banner
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
