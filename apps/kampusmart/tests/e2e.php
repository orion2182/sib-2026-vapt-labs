<?php
declare(strict_types=1);

/**
 * KampusMart end-to-end checks.
 *
 * Proves normal commerce, the three bounded conditions, and the patched
 * retest while the legitimate workflow still succeeds.
 *
 * Usage: php tests/e2e.php --base http://127.0.0.1:8081 --variant vulnerable
 */

$options = getopt('', ['base:', 'variant:']);
$base = rtrim($options['base'] ?? 'http://127.0.0.1:8081', '/');
$variant = $options['variant'] ?? 'vulnerable';
if (!in_array($variant, ['vulnerable', 'patched'], true)) {
    fwrite(STDERR, "variant must be vulnerable or patched\n");
    exit(2);
}

$passed = 0;
$failed = 0;
$failures = [];

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed, $failures;
    if ($condition) {
        $passed++;
        echo "PASS  $name\n";
    } else {
        $failed++;
        $failures[] = $name . ($detail !== '' ? " — $detail" : '');
        echo "FAIL  $name" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

/** @return array{status:int, body:string, json:?array} */
function http(string $method, string $path, $body = null, ?string $jar = null, array $headers = []): array
{
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
    ]);
    if ($jar !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($body !== null) {
        $payload = json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            ['Accept: application/json', 'Content-Type: application/json'],
            $headers
        ));
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $decoded = json_decode((string) $raw, true);
    return ['status' => $status, 'body' => (string) $raw, 'json' => is_array($decoded) ? $decoded : null];
}

function jar(string $name): string
{
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'km-' . $name . '-' . getmypid() . '.cookies';
    if (is_file($path)) {
        unlink($path);
    }
    return $path;
}

$health = http('GET', '/healthz');
check('health endpoint responds', $health['status'] === 200, 'status ' . $health['status']);
check('health reports expected variant', ($health['json']['variant'] ?? null) === $variant, (string) ($health['json']['variant'] ?? 'null'));
check('health reports database ok', ($health['json']['checks']['database'] ?? null) === 'ok');

// --- Normal flow: customer A browses, orders, sees own invoice -------------
$jarA = jar('a');
$loginA = http('POST', '/login', ['email' => 'aurel@kampusmart.test', 'password' => 'LabPass!2026'], $jarA);
check('customer A can sign in', $loginA['status'] === 200, 'status ' . $loginA['status']);

$catalog = http('GET', '/catalog', null, $jarA);
check('catalog lists synthetic products', $catalog['status'] === 200 && count($catalog['json']['products'] ?? []) >= 10);

$add = http('POST', '/cart/add', ['product_id' => 103, 'qty' => 2], $jarA);
check('customer can add to cart', $add['status'] === 200);

$order = http('POST', '/order', null, $jarA);
check('customer can create an order', $order['status'] === 201, 'status ' . $order['status'] . ' ' . substr($order['body'], 0, 120));
$orderId = (int) ($order['json']['order_id'] ?? 0);
$invoiceId = (int) ($order['json']['invoice_id'] ?? 0);
$subtotal = (int) ($order['json']['total_cents'] ?? 0);
check('order returns invoice id', $orderId > 0 && $invoiceId > 0);

$ownInvoice = http('GET', '/invoice?id=' . $invoiceId, null, $jarA);
check('owner can read own invoice', $ownInvoice['status'] === 200, 'status ' . $ownInvoice['status']);
check('new invoice has a synthetic marker', str_starts_with((string) ($ownInvoice['json']['invoice']['marker'] ?? ''), 'KM-AUTO-'));

// --- Normal flow: one valid coupon use -----------------------------------
$coupon = http('POST', '/coupon/apply', ['code' => 'HEMAT10', 'order_id' => $orderId], $jarA);
check('first valid coupon use succeeds', $coupon['status'] === 200, 'status ' . $coupon['status'] . ' ' . substr($coupon['body'], 0, 120));
check('coupon discount matches 10 percent', (int) ($coupon['json']['discount_cents'] ?? -1) === (int) round($subtotal * 0.10));

// --- Bounded condition 3: coupon reuse beyond the one-use rule -----------
$second = http('POST', '/coupon/apply', ['code' => 'HEMAT10', 'order_id' => $orderId], $jarA);
if ($variant === 'vulnerable') {
    check(
        'VULN coupon can be reused beyond max_uses',
        $second['status'] === 200 && (int) ($second['json']['coupon_uses'] ?? 0) > 1,
        'status ' . $second['status'] . ' uses ' . ($second['json']['coupon_uses'] ?? 'n/a')
    );
} else {
    check(
        'PATCHED coupon reuse is rejected',
        $second['status'] === 409,
        'status ' . $second['status'] . ' ' . substr($second['body'], 0, 120)
    );
}

// --- Staff flow ----------------------------------------------------------
$jarS = jar('staff');
$loginS = http('POST', '/login', ['email' => 'sari@kampusmart.test', 'password' => 'StaffPass!2026'], $jarS);
check('staff can sign in', $loginS['status'] === 200, 'status ' . $loginS['status']);
$staffOrders = http('GET', '/staff/orders', null, $jarS);
check('staff sees all orders', $staffOrders['status'] === 200 && count($staffOrders['json']['orders'] ?? []) >= 2);
$statusUpdate = http('POST', '/staff/order/status', ['order_id' => $orderId, 'status' => 'paid'], $jarS);
check('staff can advance an order', $statusUpdate['status'] === 200);
$customerStaffCall = http('GET', '/staff/orders', null, $jarA);
check('customer cannot use the staff listing', $customerStaffCall['status'] === 403, 'status ' . $customerStaffCall['status']);

// --- Normal flow: customer B sees own pre-seeded invoice -----------------
$jarB = jar('b');
$loginB = http('POST', '/login', ['email' => 'bima@kampusmart.test', 'password' => 'LabPass!2026'], $jarB);
check('customer B can sign in', $loginB['status'] === 200);
$ownB = http('GET', '/invoice?id=7002', null, $jarB);
check('customer B can read own seeded invoice', $ownB['status'] === 200);
check('seeded invoice marker is present', ($ownB['json']['invoice']['marker'] ?? '') === 'KM-B-INVOICE-9X4');

// --- Bounded condition 2: foreign invoice ownership ----------------------
$foreign = http('GET', '/invoice?id=7001', null, $jarB);
if ($variant === 'vulnerable') {
    check(
        'VULN customer B reads customer A invoice',
        $foreign['status'] === 200 && ($foreign['json']['invoice']['marker'] ?? '') === 'KM-A-INVOICE-7Q2',
        'status ' . $foreign['status']
    );
} else {
    check(
        'PATCHED foreign invoice is rejected',
        $foreign['status'] === 403,
        'status ' . $foreign['status']
    );
}

// Staff may read any invoice in both builds.
$staffInvoice = http('GET', '/invoice?id=7001', null, $jarS);
check('staff may read a customer invoice', $staffInvoice['status'] === 200, 'status ' . $staffInvoice['status']);

// --- Bounded condition 1: legacy document service traversal --------------
$info = http('GET', '/legacy/info');
check('legacy service exposes observable configuration', $info['status'] === 200 && ($info['json']['service'] ?? '') === 'kampusmart-legacy-docs');

$publicDoc = http('GET', '/legacy/files?name=welcome.txt');
check('legacy service serves a public document', $publicDoc['status'] === 200 && str_contains($publicDoc['body'], 'Selamat datang'));

$guide = http('GET', '/legacy/files?name=panduan-pengembalian.txt');
check('legacy service serves the listed guide', $guide['status'] === 200 && str_contains($guide['body'], 'Panduan pengembalian'));

$marker = http('GET', '/legacy/files?name=' . rawurlencode('../private/marker-km01.txt'));
if ($variant === 'vulnerable') {
    check(
        'VULN legacy service exposes the synthetic marker',
        $marker['status'] === 200 && str_contains($marker['body'], 'KM-MARKER-4F9A2C'),
        'status ' . $marker['status']
    );
} else {
    check(
        'PATCHED legacy traversal is rejected',
        $marker['status'] === 403,
        'status ' . $marker['status'] . ' ' . substr($marker['body'], 0, 120)
    );
}

// --- Evidence trail ------------------------------------------------------
$evidence = http('GET', '/staff/evidence?limit=200', null, $jarS);
check('evidence log is readable by staff', $evidence['status'] === 200);
$actions = array_column($evidence['json']['events'] ?? [], 'action');
check('evidence records invoice reads', in_array('invoice.read', $actions, true));
check('evidence records coupon decisions', in_array('coupon.apply', $actions, true));
check('evidence records legacy reads', in_array('legacy.document.read', $actions, true));
$leaked = false;
foreach ($evidence['json']['events'] ?? [] as $event) {
    if (stripos((string) $event['detail'], 'password') !== false || stripos((string) $event['detail'], 'LabPass') !== false) {
        $leaked = true;
    }
}
check('evidence contains no password material', !$leaked);

echo "\n$passed passed, $failed failed (variant: $variant)\n";
if ($failed > 0) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "  - $failure\n");
    }
    exit(1);
}
exit(0);
