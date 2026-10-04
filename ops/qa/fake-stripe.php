<?php

// Local stand-in for Stripe's Checkout API, for browser QA in an environment that cannot reach stripe.com.
// The app talks to it only when APP_ENV=local and STRIPE_API_BASE points here. It records each session's line items
// (so QA can compare the charged amount with the page), keeps a product/price catalogue (StripeCatalog), and serves a
// page with Pay and Cancel buttons.
// Usage: php -S 127.0.0.1:12111 ops/qa/fake-stripe.php     Sessions: /tmp/fake-stripe-sessions.json
$store = getenv('FAKE_STRIPE_STORE') ?: sys_get_temp_dir().'/fake-stripe-sessions.json';
$sessions = is_file($store) ? json_decode((string) file_get_contents($store), true) : [];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = 'http://'.$_SERVER['HTTP_HOST'];
header('Request-Id: req_fake');

// Catalogue: products with caller-chosen ids and prices found by lookup key, as StripeCatalog uses them.
$catFile = preg_replace('/\.json$/', '', $store).'-catalogue.json';
$cat = is_file($catFile) ? json_decode((string) file_get_contents($catFile), true) : ['products' => [], 'prices' => []];
$json = function ($data, int $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);

    return true;
};
$missing = fn ($what) => $json(['error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => 'No such '.$what]], 404);
parse_str((string) file_get_contents('php://input'), $body);
if (preg_match('#^/v1/products(?:/([^/]+))?$#', $path, $m)) {
    $id = $m[1] ?? ($body['id'] ?? 'prod_fake_'.(count($cat['products']) + 1));
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return isset($cat['products'][$id]) ? $json($cat['products'][$id]) : $missing('product: '.$id);
    }
    $cat['products'][$id] = array_merge($cat['products'][$id] ?? ['id' => $id, 'object' => 'product', 'active' => true], $body, ['active' => ($body['active'] ?? 'true') !== 'false']);
    file_put_contents($catFile, json_encode($cat));

    return $json($cat['products'][$id]);
}
if (preg_match('#^/v1/prices(?:/([^/]+))?$#', $path, $m)) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $keys = $_GET['lookup_keys'] ?? [];
        $data = array_values(array_filter($cat['prices'], fn ($p) => in_array($p['lookup_key'] ?? null, $keys, true)));

        return $json(['object' => 'list', 'url' => '/v1/prices', 'has_more' => false, 'data' => array_slice($data, 0, 1)]);
    }
    if (isset($m[1])) {
        if (! isset($cat['prices'][$m[1]])) {
            return $missing('price: '.$m[1]);
        }
        $cat['prices'][$m[1]]['active'] = ($body['active'] ?? 'true') !== 'false';
    } else {
        $id = 'price_fake_'.(count($cat['prices']) + 1);
        if (($body['transfer_lookup_key'] ?? '') === 'true') {
            foreach ($cat['prices'] as &$old) {
                if (($old['lookup_key'] ?? null) === $body['lookup_key']) {
                    $old['lookup_key'] = null;
                }
            }
            unset($old);
        }
        $cat['prices'][$id] = ['id' => $id, 'object' => 'price', 'active' => true, 'type' => 'one_time', 'product' => $body['product'], 'currency' => $body['currency'], 'unit_amount' => (int) $body['unit_amount'], 'lookup_key' => $body['lookup_key'] ?? null, 'nickname' => $body['nickname'] ?? null];
        $m[1] = $id;
    }
    file_put_contents($catFile, json_encode($cat));

    return $json($cat['prices'][$m[1]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === '/v1/checkout/sessions') {
    $p = $body;
    $id = 'cs_test_fake_'.(count($sessions) + 1);
    $item = $p['line_items'][0]['price_data'] ?? [];
    if (isset($p['line_items'][0]['price'])) { // a catalogue price chosen by the server
        $cp = $cat['prices'][$p['line_items'][0]['price']] ?? null;
        if (! $cp || ! $cp['active']) {
            return $missing('price: '.$p['line_items'][0]['price']);
        }
        $item = ['unit_amount' => $cp['unit_amount'], 'currency' => $cp['currency'], 'product_data' => ['name' => $cat['products'][$cp['product']]['name'] ?? $cp['product']]];
    }
    $sessions[$id] = ['id' => $id, 'amount_total' => (int) ($item['unit_amount'] ?? 0), 'currency' => $item['currency'] ?? '', 'name' => $item['product_data']['name'] ?? '', 'price' => $p['line_items'][0]['price'] ?? null,
        'metadata' => $p['metadata'] ?? [], 'success_url' => str_replace('{CHECKOUT_SESSION_ID}', $id, $p['success_url'] ?? ''), 'cancel_url' => $p['cancel_url'] ?? ''];
    file_put_contents($store, json_encode($sessions));
    header('Content-Type: application/json');
    echo json_encode(['id' => $id, 'object' => 'checkout.session', 'url' => $base.'/pay/'.$id, 'payment_intent' => null, 'amount_total' => $sessions[$id]['amount_total'], 'currency' => $sessions[$id]['currency']]);

    return true;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && preg_match('#^/v1/checkout/sessions/([^/]+)/expire$#', $path, $m)) {
    header('Content-Type: application/json');
    echo json_encode(['id' => $m[1], 'object' => 'checkout.session', 'status' => 'expired']);

    return true;
}
if (preg_match('#^/pay/([^/]+)$#', $path, $m) && isset($sessions[$m[1]])) {
    $s = $sessions[$m[1]];
    $amount = number_format($s['amount_total'] / 100, 2).' '.strtoupper($s['currency']);
    echo '<!doctype html><meta name="viewport" content="width=device-width"><title>Test checkout</title><body style="font-family:sans-serif;padding:24px">'
        .'<p>LOCAL TEST CHECKOUT (stands in for Stripe)</p><h1 data-name>'.htmlspecialchars($s['name']).'</h1><p data-amount>'.$amount.'</p>'
        .'<a data-pay href="'.htmlspecialchars($s['success_url']).'">Pay</a> &nbsp; <a data-cancel href="'.htmlspecialchars($s['cancel_url']).'">Cancel</a></body>';

    return true;
}
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => ['message' => 'fake-stripe: no route for '.$path]]);
