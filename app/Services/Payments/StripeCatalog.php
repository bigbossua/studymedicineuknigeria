<?php

namespace App\Services\Payments;

use App\Models\TierPrice;
use Stripe\Exception\InvalidRequestException;

/**
 * The Stripe side of our own price records: one Product per service (fixed id smukn_t1, smukn_t2, smukn_t3) and one
 * active one-time GBP Price per product, found by its lookup key (smukn_t1_gbp, ...). tier_prices stays the single
 * source of the amount; Stripe holds a matching catalogue entry so Checkout charges a fixed Price chosen by the server.
 *
 * Safe to repeat: products are fetched by id before any create, prices by lookup key; a new Price is created only when
 * none exists or the amount was changed by an admin (the old one is then deactivated and its lookup key moved). No
 * Payment Links are ever created, so nothing can be bought outside the approved-student workflow.
 */
class StripeCatalog
{
    public function __construct(private StripeService $stripe) {}

    public static function productId(TierPrice $price): string
    {
        return 'smukn_'.strtolower($price->tier->code).($price->component !== 'full' ? '_'.$price->component : '');
    }

    public static function lookupKey(TierPrice $price): string
    {
        return self::productId($price).'_'.strtolower($price->currency);
    }

    /** The Stripe Price id that charges exactly this record's amount in this mode, creating the catalogue entry once. */
    public function ensure(TierPrice $price): string
    {
        $price->loadMissing('tier');
        if ($price->amount_minor === null) {
            throw new \RuntimeException('Price '.$price->id.' has no amount; nothing can be charged for it.');
        }
        $live = $this->stripe->liveAllowed(); // production runs on live keys only, every other environment on test keys
        if ($price->stripe_price_id && (int) $price->stripe_price_amount === (int) $price->amount_minor && $price->stripe_livemode === $live) {
            return $price->stripe_price_id;
        }

        return $this->sync($price)['price_id'];
    }

    /**
     * Finds or creates the product and price for one record and stores their ids. With $create false nothing is written
     * at Stripe (a read-only check). Returns what was found or done.
     *
     * @return array{code: string, product_id: string, product: string, price_id: ?string, price: string, amount_minor: int, currency: string, lookup_key: string}
     */
    public function sync(TierPrice $price, bool $create = true): array
    {
        $price->loadMissing('tier');
        $client = $this->stripe->client();
        $productId = self::productId($price);
        $key = self::lookupKey($price);
        $amount = (int) $price->amount_minor;
        $currency = strtolower($price->currency);
        $result = ['code' => $price->tier->code, 'product_id' => $productId, 'price_id' => null, 'amount_minor' => $amount, 'currency' => $currency, 'lookup_key' => $key];

        $result['product'] = $this->ensureProduct($client, $price, $productId, $create);

        $existing = $client->prices->all(['lookup_keys' => [$key], 'limit' => 1])->data[0] ?? null;
        $matches = $existing && $existing->active && (int) $existing->unit_amount === $amount && strtolower((string) $existing->currency) === $currency
            && ($existing->type ?? 'one_time') === 'one_time' && (is_string($existing->product) ? $existing->product : $existing->product->id ?? null) === $productId;
        if ($matches) {
            $result['price_id'] = $existing->id;
            $result['price'] = 'found';
            if (! $create) {
                $result['other_active'] = count(array_filter($client->prices->all(['product' => $productId, 'active' => true, 'limit' => 100])->data ?? [], fn ($p) => $p->id !== $existing->id));

                return $result;
            }
        } elseif (! $create) {
            $result['price'] = $existing ? 'differs (amount, currency, type or product)' : 'missing';
            $result['other_active'] = $result['product'] === 'missing' ? 0 : count($client->prices->all(['product' => $productId, 'active' => true, 'limit' => 100])->data ?? []);

            return $result;
        } else {
            $created = $client->prices->create([
                'product' => $productId, 'currency' => $currency, 'unit_amount' => $amount, 'lookup_key' => $key, 'transfer_lookup_key' => true,
                'nickname' => $price->tier->code.' one-time service fee', 'metadata' => ['tier' => $price->tier->code, 'tier_price_id' => (string) $price->id, 'source' => 'tier_prices'],
            ]);
            if ($existing && $existing->active) {
                $client->prices->update($existing->id, ['active' => false]); // the superseded amount can no longer be charged
            }
            $result['price_id'] = $created->id;
            $result['price'] = $existing ? 'created (replaces '.$existing->id.')' : 'created';
        }

        // exactly one active price per service: any other active price on our product is switched off (never deleted)
        $others = collect($client->prices->all(['product' => $productId, 'active' => true, 'limit' => 100])->data ?? [])->filter(fn ($p) => $p->id !== $result['price_id']);
        foreach ($others as $other) {
            $client->prices->update($other->id, ['active' => false]);
        }
        $result['deactivated'] = $others->pluck('id')->values()->all();

        $price->forceFill(['stripe_product_id' => $productId, 'stripe_price_id' => $result['price_id'], 'stripe_price_amount' => $amount, 'stripe_livemode' => $this->stripe->liveAllowed()])->save();

        return $result;
    }

    /**
     * Active Payment Links that sell one of our service prices: they would let anyone pay without the profile review.
     * Reported, never changed here (the owner decides).
     *
     * @param  array<int, string>  $priceIds
     * @return array<int, string> ids of the offending links
     */
    public function paymentLinksSelling(array $priceIds, array $productIds): array
    {
        $client = $this->stripe->client();
        $bad = [];
        foreach ($client->paymentLinks->all(['active' => true, 'limit' => 100])->data ?? [] as $link) {
            foreach ($client->paymentLinks->allLineItems($link->id, ['limit' => 100])->data ?? [] as $item) {
                $priceId = is_string($item->price ?? null) ? $item->price : ($item->price->id ?? null);
                $productId = is_object($item->price ?? null) ? (is_string($item->price->product ?? null) ? $item->price->product : null) : null;
                if (in_array($priceId, $priceIds, true) || in_array($productId, $productIds, true)) {
                    $bad[] = $link->id;
                    break;
                }
            }
        }

        return $bad;
    }

    private function ensureProduct(object $client, TierPrice $price, string $id, bool $create): string
    {
        $name = $price->tier->name;
        $description = 'Study Medicine UK Nigeria service fee. '.$price->tier->summary.' Separate from university tuition, application, test and visa fees; no admission is guaranteed.';
        try {
            $product = $client->products->retrieve($id);
        } catch (InvalidRequestException $e) {
            if ($e->getHttpStatus() !== 404) {
                throw $e;
            }
            if (! $create) {
                return 'missing';
            }
            $client->products->create(['id' => $id, 'name' => $name, 'description' => $description, 'metadata' => ['tier' => $price->tier->code, 'source' => 'service_tiers']]);

            return 'created';
        }
        if ($create && (! $product->active || $product->name !== $name || ($product->description ?? '') !== $description)) {
            $client->products->update($id, ['active' => true, 'name' => $name, 'description' => $description]);

            return 'updated';
        }

        return 'found';
    }
}
