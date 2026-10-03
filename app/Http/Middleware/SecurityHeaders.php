<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // One nonce per response; @vite stamps it on every script and style tag it emits.
        $nonce = Vite::useCspNonce();
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(self "https://checkout.stripe.com")');
        if ($request->isSecure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        // Enforced since stage 8. Scripts: only our Vite bundles (nonce) – no inline handlers exist in the views.
        // Styles: Vite bundle plus inline style attributes (progress widths); fonts self-hosted; Stripe Checkout is a
        // hosted redirect so only form-action/frame-src need its hosts. JSON-LD data blocks are not subject to script-src.
        $ga = config('site.ga4_id') && ! $request->is('portal*', 'admin*'); // third-party analytics never load inside the portal or admin
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'".($ga ? ' https://www.googletagmanager.com' : ''),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'".($ga ? ' https://www.googletagmanager.com https://*.google-analytics.com https://*.analytics.google.com' : ''),
            'frame-src https://checkout.stripe.com https://js.stripe.com',
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https://checkout.stripe.com",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->remove('Content-Security-Policy-Report-Only');

        $private = $request->is('portal*', 'admin*', 'login', 'register', 'password*', 'email*', 'documents*', 'webhooks*');
        if ($private) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
