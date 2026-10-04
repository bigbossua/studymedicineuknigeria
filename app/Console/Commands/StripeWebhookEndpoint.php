<?php

namespace App\Console\Commands;

use App\Services\Payments\StripeService;
use Illuminate\Console\Command;

/**
 * Checks (or, with --create, makes) the Stripe webhook endpoint for this site: URL <APP_URL>/webhooks/stripe, enabled,
 * sending at least StripeService::WEBHOOK_EVENTS. Stripe reveals a new endpoint's signing secret only once, so --create
 * writes it to --secret-file (mode 600) and never prints it; the Stripe catalogue workflow then hands it to the server.
 */
class StripeWebhookEndpoint extends Command
{
    protected $signature = 'smukn:stripe-webhook {--url= : Endpoint URL (default APP_URL/webhooks/stripe)} {--create : Create the endpoint when none exists} {--secret-file= : Where --create writes the signing secret}';

    protected $description = 'Verify or create the Stripe webhook endpoint and its events (the signing secret is never printed)';

    public function handle(StripeService $stripe): int
    {
        if (! $stripe->enabled()) {
            $this->error('No usable Stripe secret key for this environment ('.app()->environment().').');

            return self::FAILURE;
        }
        $url = $this->option('url') ?: rtrim((string) config('app.url'), '/').'/webhooks/stripe';
        if (! str_starts_with($url, 'https://')) {
            $this->error("Stripe delivers webhooks to public https URLs only; $url is not one.");

            return self::FAILURE;
        }
        $client = $stripe->client();
        $mine = collect($client->webhookEndpoints->all(['limit' => 100])->data ?? [])->filter(fn ($e) => rtrim($e->url, '/') === rtrim($url, '/'))->values();
        $mode = $stripe->liveAllowed() ? 'live' : 'test';

        if ($mine->isEmpty()) {
            if (! $this->option('create')) {
                $this->error("No {$mode}-mode webhook endpoint for {$url}.");

                return self::FAILURE;
            }
            $file = (string) $this->option('secret-file');
            if ($file === '') {
                $this->error('--create needs --secret-file: the signing secret is shown only once and must go straight to the server.');

                return self::FAILURE;
            }
            $endpoint = $client->webhookEndpoints->create(['url' => $url, 'enabled_events' => StripeService::WEBHOOK_EVENTS, 'description' => 'Study Medicine UK Nigeria: service fee payments ('.app()->environment().')']);
            $old = umask(077);
            file_put_contents($file, $endpoint->secret);
            umask($old);
            chmod($file, 0600);
            $this->info("Created {$mode}-mode endpoint {$endpoint->id} for {$url} with ".count(StripeService::WEBHOOK_EVENTS).' events; signing secret written to the secret file (not shown).');

            return self::SUCCESS;
        }

        $problems = 0;
        if ($mine->count() > 1) {
            $this->warn($mine->count().' endpoints point at '.$url.' ('.$mine->pluck('id')->implode(', ').'): each delivers every event once; keep one.');
            $problems++;
        }
        foreach ($mine as $e) {
            $events = (array) $e->enabled_events;
            $missing = in_array('*', $events, true) ? [] : array_values(array_diff(StripeService::WEBHOOK_EVENTS, $events));
            $this->line("{$mode}-mode endpoint {$e->id}: status {$e->status}, ".count($events).' events'.($missing ? ', MISSING: '.implode(', ', $missing) : ', all required events present'));
            $problems += ($e->status !== 'enabled' ? 1 : 0) + count($missing);
        }

        return $problems ? self::FAILURE : self::SUCCESS;
    }
}
