<?php

// Local stand-in for Stripe's Checkout API, for browser QA in an environment that cannot reach stripe.com.
// The app talks to it only when APP_ENV=local and STRIPE_API_BASE points here. It records each session's line items
// (so QA can compare the charged amount with the page) and serves a page with Pay and Cancel buttons.
// Usage: php -S 127.0.0.1:12111 ops/qa/fake-stripe.php     Sessions: /tmp/fake-stripe-sessions.json
$store = getenv('FAKE_STRIPE_STORE') ?: sys_get_temp_dir().'/fake-stripe-sessions.json';
$sessions = is_file($store) ? json_decode((string) file_get_contents($store), true) : [];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = 'http://'.$_SERVER['HTTP_HOST'];
header('Request-Id: req_fake');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $path === '/v1/checkout/sessions') {
    parse_str((string) file_get_contents('php://input'), $p);
    $id = 'cs_test_fake_'.(count($sessions) + 1);
    $item = $p['line_items'][0]['price_data'] ?? [];
    $sessions[$id] = ['id' => $id, 'amount_total' => (int) ($item['unit_amount'] ?? 0), 'currency' => $item['currency'] ?? '', 'name' => $item['product_data']['name'] ?? '',
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
