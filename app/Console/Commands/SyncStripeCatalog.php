<?php

namespace App\Console\Commands;

use App\Models\TierPrice;
use App\Services\Payments\StripeCatalog;
use App\Services\Payments\StripeService;
use Illuminate\Console\Command;

/**
 * Makes Stripe's catalogue match our service price records: one product per service and one active one-time GBP price
 * each (App\Services\Payments\StripeCatalog). Repeating it changes nothing; --check only reads. Prints ids and amounts,
 * never a key. Production uses live keys only, other environments test keys only (StripeService::enabled()).
 */
class SyncStripeCatalog extends Command
{
    protected $signature = 'smukn:stripe-sync {--check : Report what exists at Stripe without creating or changing anything}';

    protected $description = 'Create or verify the Stripe products and one-time GBP prices for the service fees (no duplicates, no payment links)';

    public function handle(StripeService $stripe, StripeCatalog $catalog): int
    {
        if (! $stripe->enabled()) {
            $this->error('No usable Stripe secret key for this environment ('.app()->environment().'): production takes live keys only, other environments test keys only.');

            return self::FAILURE;
        }
        $mode = $stripe->liveAllowed() ? 'live' : 'test';
        $rows = [];
        $problems = 0;
        foreach (TierPrice::with('tier')->where('active', true)->whereNotNull('amount_minor')->get()->sortBy(fn ($p) => $p->tier->sort) as $price) {
            $r = $catalog->sync($price, ! $this->option('check'));
            $problems += $r['price_id'] === null ? 1 : 0;
            $rows[] = [$r['code'], $price->tier->name, number_format($r['amount_minor'] / 100, 2).' '.strtoupper($r['currency']), $r['product_id'].' ('.$r['product'].')', ($r['price_id'] ?? '—').' ('.$r['price'].')', $r['lookup_key']];
        }
        $this->info("Stripe catalogue, {$mode} mode".($this->option('check') ? ' (check only, nothing changed)' : ''));
        $this->table(['Service', 'Name', 'Amount', 'Product', 'Price', 'Lookup key'], $rows);

        return $problems ? self::FAILURE : self::SUCCESS;
    }
}
