<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read-only Google Search Console client for the site's own property, signed in as a service account the owner added
 * to the property as a user (docs/ops/SEARCH-CONSOLE-AND-GA4.md, "Data feed"). Scope webmasters.readonly: it can read
 * performance data and inspect URLs, never change the property, submit sitemaps or request indexing.
 * The key arrives base64-encoded in GSC_SERVICE_ACCOUNT (written by update-env-hostinger.yml); it is never logged.
 */
class SearchConsole
{
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public static function configured(): bool
    {
        $key = self::key();

        return $key !== null && filled($key['client_email'] ?? null) && filled($key['private_key'] ?? null);
    }

    public static function property(): string
    {
        return (string) config('services.gsc.property');
    }

    /** The service account's email address (safe to show: the owner adds it to the property as a user). */
    public static function accountEmail(): ?string
    {
        return self::key()['client_email'] ?? null;
    }

    /**
     * One page of Search Analytics rows for [date, query, page, country], web search only.
     *
     * @return list<array{keys: list<string>, clicks: float, impressions: float, ctr: float, position: float}>
     */
    public function searchAnalytics(string $start, string $end, int $startRow = 0, int $rowLimit = 25000): array
    {
        $res = Http::withToken($this->token())->timeout(60)->acceptJson()
            ->post('https://www.googleapis.com/webmasters/v3/sites/'.rawurlencode(self::property()).'/searchAnalytics/query', [
                'startDate' => $start, 'endDate' => $end, 'type' => 'web', 'dataState' => 'final',
                'dimensions' => ['date', 'query', 'page', 'country'], 'rowLimit' => $rowLimit, 'startRow' => $startRow,
            ]);
        $this->fail($res, 'search analytics');

        return $res->json('rows') ?? [];
    }

    /** Google's own index status for one URL (URL Inspection API; 2,000 a day per property). */
    public function inspect(string $url): array
    {
        $res = Http::withToken($this->token())->timeout(60)->acceptJson()
            ->post('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', ['inspectionUrl' => $url, 'siteUrl' => self::property()]);
        $this->fail($res, 'URL inspection');

        return $res->json('inspectionResult.indexStatusResult') ?? [];
    }

    private function token(): string
    {
        return Cache::remember('gsc.access_token', now()->addMinutes(50), function () {
            $key = self::key() ?? throw new RuntimeException('Search Console is not connected (GSC_SERVICE_ACCOUNT is empty).');
            $now = time();
            $segments = [self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), self::b64(json_encode([
                'iss' => $key['client_email'], 'scope' => self::SCOPE, 'aud' => self::TOKEN_URL, 'iat' => $now, 'exp' => $now + 3600,
            ]))];
            $pkey = openssl_pkey_get_private($key['private_key']) ?: throw new RuntimeException('The Search Console service-account key is not a valid private key.');
            openssl_sign(implode('.', $segments), $signature, $pkey, OPENSSL_ALGO_SHA256) || throw new RuntimeException('Could not sign the Search Console token request.');
            $segments[] = self::b64($signature);
            $res = Http::asForm()->timeout(30)->post(self::TOKEN_URL, ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => implode('.', $segments)]);
            $this->fail($res, 'sign-in');

            return (string) $res->json('access_token');
        });
    }

    private function fail($res, string $what): void
    {
        if ($res->failed()) {
            // Google's error message only (never the request, which carries the token)
            throw new RuntimeException("Search Console {$what} failed: HTTP {$res->status()} ".substr((string) ($res->json('error.message') ?? $res->json('error_description') ?? ''), 0, 200));
        }
    }

    private static function key(): ?array
    {
        $raw = (string) config('services.gsc.service_account');
        if ($raw === '') {
            return null;
        }
        $json = str_starts_with(ltrim($raw), '{') ? $raw : base64_decode($raw, true);
        $key = is_string($json) ? json_decode($json, true) : null;

        return is_array($key) ? $key : null;
    }

    private static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }
}
