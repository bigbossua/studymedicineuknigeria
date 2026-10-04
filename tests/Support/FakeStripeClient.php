<?php

namespace Tests\Support;

use Stripe\Exception\InvalidRequestException;

/**
 * Stands in for Stripe\StripeClient in tests (no network): records Checkout sessions and keeps a small product/price
 * catalogue with Stripe's lookup-key and 404 behaviour, so StripeCatalog is exercised exactly as against Stripe.
 */
class FakeStripeClient
{
    public object $checkout;

    public object $products;

    public object $prices;

    /** @var array<int, array> */
    public array $created = [];

    public array $expired = [];

    /** @var array<string, object> */
    public array $productStore = [];

    /** @var array<string, object> */
    public array $priceStore = [];

    public int $productCreates = 0;

    public int $priceCreates = 0;

    public function __construct()
    {
        $root = $this;
        $this->checkout = (object) ['sessions' => new class($root)
        {
            public function __construct(private FakeStripeClient $root) {}

            public function create(array $params): object
            {
                $priceId = $params['line_items'][0]['price'] ?? null;
                if ($priceId !== null && (! isset($this->root->priceStore[$priceId]) || ! $this->root->priceStore[$priceId]->active)) {
                    throw InvalidRequestException::factory('No such price: '.$priceId, 400);
                }
                $this->root->created[] = $params;
                $id = 'cs_test_'.count($this->root->created);

                return (object) ['id' => $id, 'url' => 'https://checkout.stripe.com/c/pay/'.$id, 'payment_intent' => null];
            }

            public function expire(string $id): object
            {
                $this->root->expired[] = $id;

                return (object) ['id' => $id];
            }
        }];
        $this->products = new class($root)
        {
            public function __construct(private FakeStripeClient $root) {}

            public function retrieve(string $id): object
            {
                return $this->root->productStore[$id] ?? throw InvalidRequestException::factory('No such product: '.$id, 404, null, null, null, 'resource_missing');
            }

            public function create(array $p): object
            {
                $this->root->productCreates++;

                return $this->root->productStore[$p['id']] = (object) ($p + ['active' => true, 'object' => 'product']);
            }

            public function update(string $id, array $p): object
            {
                foreach ($p as $k => $v) {
                    $this->root->productStore[$id]->{$k} = $v;
                }

                return $this->root->productStore[$id];
            }
        };
        $this->prices = new class($root)
        {
            public function __construct(private FakeStripeClient $root) {}

            public function all(array $q): object
            {
                $data = array_values(array_filter($this->root->priceStore, fn ($p) => in_array($p->lookup_key, $q['lookup_keys'] ?? [], true)));

                return (object) ['data' => array_slice($data, 0, $q['limit'] ?? 10)];
            }

            public function create(array $p): object
            {
                $this->root->priceCreates++;
                if (! empty($p['transfer_lookup_key'])) {
                    foreach ($this->root->priceStore as $old) {
                        if ($old->lookup_key === $p['lookup_key']) {
                            $old->lookup_key = null;
                        }
                    }
                }
                $id = 'price_test_'.$this->root->priceCreates;

                return $this->root->priceStore[$id] = (object) ['id' => $id, 'object' => 'price', 'active' => true, 'type' => 'one_time', 'product' => $p['product'], 'currency' => $p['currency'], 'unit_amount' => $p['unit_amount'], 'lookup_key' => $p['lookup_key'] ?? null];
            }

            public function update(string $id, array $p): object
            {
                foreach ($p as $k => $v) {
                    $this->root->priceStore[$id]->{$k} = $v;
                }

                return $this->root->priceStore[$id];
            }
        };
    }

    /** The amount Stripe would charge for a recorded session (what the student actually pays). */
    public function chargedAmount(array $params): array
    {
        $price = $this->priceStore[$params['line_items'][0]['price']];

        return [$price->unit_amount, $price->currency];
    }
}
