<?php
declare(strict_types=1);

$packageRoot = dirname(__DIR__, 2);
if (!is_file($packageRoot . '/shared/harness/lib.php')) {
    $packageRoot = dirname(__DIR__, 3);
}
require $packageRoot . '/shared/harness/lib.php';
require dirname(__DIR__) . '/src/app.php';

$pdo = lab_db();
$variant = lab_variant();
$method = lab_method();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$actor = lab_actor($pdo);

lab_session();
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function wants_json(): bool
{
    if (($_GET['format'] ?? '') === 'json') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return stripos($accept, 'application/json') !== false;
}

function page(string $title, string $body, ?array $actor): void
{
    if (wants_json()) {
        lab_json(['error' => 'html_only']);
    }
    km_layout($title, $body, $actor);
    exit;
}

function redirect(string $to): void
{
    header('Location: ' . $to, true, 302);
    exit;
}

switch (true) {
    case $path === '/healthz' && $method === 'GET':
        $ok = true;
        try {
            $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        } catch (Throwable $e) {
            $ok = false;
        }
        lab_json([
            'status' => $ok ? 'ok' : 'degraded',
            'target' => lab_target(),
            'variant' => $variant,
            'build_id' => lab_build()['build_id'] ?? null,
            'harness_version' => LAB_HARNESS_VERSION,
            'schema_version' => 'kampusmart-1',
            'checks' => ['database' => $ok ? 'ok' : 'fail'],
            'request_id' => lab_request_id(),
        ]);
        break;

    case $path === '/' && $method === 'GET':
        redirect('/catalog');

    case $path === '/catalog' && $method === 'GET':
        $products = km_products($pdo);
        if (wants_json()) {
            lab_json(['products' => array_map(static fn (array $p): array => [
                'id' => (int) $p['id'],
                'sku' => $p['sku'],
                'name' => $p['name'],
                'price_cents' => (int) $p['price_cents'],
                'stock' => (int) $p['stock'],
            ], $products)]);
        }
        $cards = '';
        foreach ($products as $p) {
            $cards .= '<article class="card"><h2><a href="/product?id=' . (int) $p['id'] . '">' . lab_e($p['name']) . '</a></h2>'
                . '<p class="sku">' . lab_e($p['sku']) . '</p><p>' . lab_e($p['description']) . '</p>'
                . '<p class="price">' . km_money((int) $p['price_cents']) . '</p>'
                . '<p class="stock">Stok ' . (int) $p['stock'] . '</p>'
                . '<form method="post" action="/cart/add" class="inline"><input type="hidden" name="product_id" value="' . (int) $p['id'] . '">'
                . '<input type="hidden" name="qty" value="1"><button type="submit">Tambah</button></form></article>';
        }
        page('Katalog', '<h1>Katalog</h1>' . ($actor ? '' : '<p class="notice">Masuk untuk membuat pesanan.</p>')
            . '<section class="grid">' . $cards . '</section>', $actor);
        break;

    case $path === '/product' && $method === 'GET':
        $product = km_product($pdo, (int) ($_GET['id'] ?? 0));
        if (!$product) {
            lab_json(['error' => 'product_not_found'], 404);
        }
        if (wants_json()) {
            lab_json(['product' => $product]);
        }
        page($product['name'], '<h1>' . lab_e($product['name']) . '</h1><p>' . lab_e($product['description'])
            . '</p><p class="price">' . km_money((int) $product['price_cents']) . '</p>'
            . '<form method="post" action="/cart/add"><input type="hidden" name="product_id" value="' . (int) $product['id'] . '">'
            . '<label>Jumlah <input type="number" name="qty" value="1" min="1" max="99"></label> '
            . '<button type="submit">Tambah ke keranjang</button></form>', $actor);
        break;

    case $path === '/login' && $method === 'GET':
        page('Masuk', '<h1>Masuk</h1><form method="post" action="/login" class="stack">'
            . '<label>Email <input type="email" name="email" autocomplete="username" required></label>'
            . '<label>Kata sandi <input type="password" name="password" autocomplete="current-password" required></label>'
            . '<button type="submit">Masuk</button></form>'
            . '<p class="hint">Akun sintetis tersedia pada kartu target kelompok.</p>', $actor);
        break;

    case $path === '/login' && $method === 'POST':
        $body = lab_body();
        $user = lab_login($pdo, (string) ($body['email'] ?? ''), (string) ($body['password'] ?? ''));
        if (!$user) {
            if (wants_json()) {
                lab_json(['error' => 'invalid_credentials', 'request_id' => lab_request_id()], 401);
            }
            page('Masuk', '<h1>Masuk</h1><p class="error">Kredensial tidak valid.</p>'
                . '<form method="post" action="/login" class="stack"><label>Email <input type="email" name="email" required></label>'
                . '<label>Kata sandi <input type="password" name="password" required></label>'
                . '<button type="submit">Masuk</button></form>', null);
        }
        if (wants_json()) {
            lab_json(['user' => $user]);
        }
        redirect('/account');

    case $path === '/logout' && $method === 'POST':
        lab_logout($pdo);
        if (wants_json()) {
            lab_json(['ok' => true]);
        }
        redirect('/catalog');

    case $path === '/account' && $method === 'GET':
        $who = lab_require_actor($pdo);
        $orders = km_orders_for_user($pdo, (int) $who['id']);
        if (wants_json()) {
            lab_json(['user' => $who, 'orders' => $orders]);
        }
        $rows = '';
        foreach ($orders as $o) {
            $rows .= '<tr><td>' . (int) $o['id'] . '</td><td>' . lab_e($o['status']) . '</td><td>'
                . km_money((int) $o['total_cents']) . '</td><td>'
                . ($o['invoice_id'] ? '<a href="/invoice?id=' . (int) $o['invoice_id'] . '">' . lab_e((string) $o['invoice_number']) . '</a>' : '—')
                . '</td></tr>';
        }
        page('Akun', '<h1>Akun ' . lab_e($who['name']) . '</h1><p>Peran: ' . lab_e($who['role'])
            . '</p><h2>Pesanan saya</h2><table><thead><tr><th>ID</th><th>Status</th><th>Total</th><th>Invoice</th></tr></thead><tbody>'
            . ($rows ?: '<tr><td colspan="4">Belum ada pesanan.</td></tr>') . '</tbody></table>', $who);
        break;

    case $path === '/cart' && $method === 'GET':
        $cart = $_SESSION['cart'];
        $lines = [];
        $total = 0;
        foreach ($cart as $id => $qty) {
            $p = km_product($pdo, (int) $id);
            if (!$p) {
                continue;
            }
            $line = (int) $p['price_cents'] * (int) $qty;
            $total += $line;
            $lines[] = ['product' => $p, 'qty' => (int) $qty, 'line_cents' => $line];
        }
        if (wants_json()) {
            lab_json(['items' => $lines, 'total_cents' => $total]);
        }
        $html = '';
        foreach ($lines as $l) {
            $html .= '<tr><td>' . lab_e($l['product']['name']) . '</td><td>' . $l['qty'] . '</td><td>' . km_money($l['line_cents']) . '</td></tr>';
        }
        page('Keranjang', '<h1>Keranjang</h1><table><thead><tr><th>Produk</th><th>Qty</th><th>Jumlah</th></tr></thead><tbody>'
            . ($html ?: '<tr><td colspan="3">Keranjang kosong.</td></tr>') . '</tbody></table>'
            . '<p class="price">Total ' . km_money($total) . '</p>'
            . ($actor ? '<form method="post" action="/order"><button type="submit">Buat pesanan</button></form>' : '<p class="notice">Masuk untuk melanjutkan.</p>'), $actor);
        break;

    case $path === '/cart/add' && $method === 'POST':
        $body = lab_body();
        $productId = (int) ($body['product_id'] ?? 0);
        $qty = max(1, min(99, (int) ($body['qty'] ?? 1)));
        if (!km_product($pdo, $productId)) {
            lab_json(['error' => 'product_not_found'], 404);
        }
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
        if (wants_json()) {
            lab_json(['ok' => true, 'cart' => $_SESSION['cart']]);
        }
        redirect('/cart');

    case $path === '/cart/clear' && $method === 'POST':
        $_SESSION['cart'] = [];
        lab_json(['ok' => true]);

    case $path === '/order' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $result = km_create_order($pdo, $who, $_SESSION['cart']);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason']], 400);
        }
        $_SESSION['cart'] = [];
        lab_evidence($pdo, $who, 'order.create', 'order', (string) $result['order_id'], 'allowed', [
            'invoice_id' => $result['invoice_id'],
            'total_cents' => $result['total_cents'],
        ]);
        if (wants_json()) {
            lab_json($result, 201);
        }
        redirect('/orders');

    case $path === '/orders' && $method === 'GET':
        $who = lab_require_actor($pdo);
        $orders = km_orders_for_user($pdo, (int) $who['id']);
        if (wants_json()) {
            lab_json(['orders' => $orders]);
        }
        $rows = '';
        foreach ($orders as $o) {
            $rows .= '<tr><td>' . (int) $o['id'] . '</td><td>' . lab_e($o['status']) . '</td><td>'
                . km_money((int) $o['subtotal_cents']) . '</td><td>' . km_money((int) $o['discount_cents']) . '</td><td>'
                . km_money((int) $o['total_cents']) . '</td><td>'
                . ($o['invoice_id'] ? '<a href="/invoice?id=' . (int) $o['invoice_id'] . '">' . lab_e((string) $o['invoice_number']) . '</a>' : '—')
                . '</td></tr>';
        }
        page('Pesanan', '<h1>Pesanan</h1><table><thead><tr><th>ID</th><th>Status</th><th>Subtotal</th><th>Diskon</th><th>Total</th><th>Invoice</th></tr></thead><tbody>'
            . ($rows ?: '<tr><td colspan="6">Belum ada pesanan.</td></tr>') . '</tbody></table>', $who);
        break;

    case $path === '/invoice' && $method === 'GET':
        $who = lab_require_actor($pdo);
        $invoiceId = (int) ($_GET['id'] ?? 0);
        $result = km_invoice_for($pdo, $who, $invoiceId, $variant);
        $crossOwner = $result['ok'] && (int) $result['invoice']['user_id'] !== (int) $who['id'];
        lab_evidence(
            $pdo,
            $who,
            'invoice.read',
            'invoice',
            (string) $invoiceId,
            $result['ok'] ? 'allowed' : 'denied',
            ['reason' => $result['reason'] ?? null, 'cross_owner' => $crossOwner]
        );
        if (!$result['ok']) {
            lab_json(['error' => $result['reason'], 'request_id' => lab_request_id()], $result['status']);
        }
        $invoice = $result['invoice'];
        if (wants_json()) {
            lab_json(['invoice' => $invoice, 'request_id' => lab_request_id()]);
        }
        page('Invoice', '<h1>Invoice ' . lab_e($invoice['number']) . '</h1><dl class="facts">'
            . '<dt>Pemilik</dt><dd>' . lab_e($invoice['owner_email']) . '</dd>'
            . '<dt>Status</dt><dd>' . lab_e($invoice['status']) . '</dd>'
            . '<dt>Subtotal</dt><dd>' . km_money((int) $invoice['subtotal_cents']) . '</dd>'
            . '<dt>Diskon</dt><dd>' . km_money((int) $invoice['discount_cents']) . '</dd>'
            . '<dt>Total</dt><dd>' . km_money((int) $invoice['total_cents']) . '</dd>'
            . '<dt>Marker</dt><dd><code>' . lab_e($invoice['marker']) . '</code></dd></dl>', $who);
        break;

    case $path === '/coupon/apply' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $result = km_apply_coupon($pdo, $who, (string) ($body['code'] ?? ''), (int) ($body['order_id'] ?? 0), $variant);
        lab_evidence(
            $pdo,
            $who,
            'coupon.apply',
            'order',
            (string) (int) ($body['order_id'] ?? 0),
            $result['ok'] ? 'allowed' : 'denied',
            [
                'code' => strtoupper((string) ($body['code'] ?? '')),
                'reason' => $result['reason'] ?? null,
                'coupon_uses' => $result['coupon_uses'] ?? null,
                'max_uses' => $result['max_uses'] ?? null,
            ]
        );
        if (!$result['ok']) {
            lab_json(['error' => $result['reason'], 'request_id' => lab_request_id()], $result['status']);
        }
        lab_json([
            'ok' => true,
            'discount_cents' => $result['discount_cents'],
            'total_cents' => $result['total_cents'],
            'coupon_uses' => $result['coupon_uses'],
            'max_uses' => $result['max_uses'],
            'request_id' => lab_request_id(),
        ]);
        break;

    case $path === '/legacy/files' && $method === 'GET':
        $name = (string) ($_GET['name'] ?? '');
        $result = km_legacy_read($name, $variant);
        lab_evidence(
            $pdo,
            $actor,
            'legacy.document.read',
            'document',
            substr($name, 0, 120),
            $result['ok'] ? 'allowed' : 'denied',
            ['reason' => $result['reason'] ?? null, 'sha256' => $result['sha256'] ?? null]
        );
        if (!$result['ok']) {
            lab_json(['error' => $result['reason'], 'request_id' => lab_request_id()], $result['status']);
        }
        if (wants_json()) {
            lab_json($result);
        }
        header('Content-Type: text/plain; charset=utf-8');
        echo $result['content'];
        break;

    case $path === '/staff/orders' && $method === 'GET':
        $who = lab_require_actor($pdo);
        lab_require_role($who, 'staff');
        $orders = km_all_orders($pdo);
        if (wants_json()) {
            lab_json(['orders' => $orders]);
        }
        $rows = '';
        foreach ($orders as $o) {
            $rows .= '<tr><td>' . (int) $o['id'] . '</td><td>' . lab_e($o['customer_email']) . '</td><td>'
                . lab_e($o['status']) . '</td><td>' . km_money((int) $o['total_cents']) . '</td><td>'
                . lab_e((string) $o['invoice_number']) . '</td></tr>';
        }
        page('Pesanan Staf', '<h1>Semua pesanan</h1><table><thead><tr><th>ID</th><th>Pelanggan</th><th>Status</th><th>Total</th><th>Invoice</th></tr></thead><tbody>'
            . $rows . '</tbody></table>', $who);
        break;

    case $path === '/staff/order/status' && $method === 'POST':
        $who = lab_require_actor($pdo);
        lab_require_role($who, 'staff');
        $body = lab_body();
        $result = km_update_status($pdo, (int) ($body['order_id'] ?? 0), (string) ($body['status'] ?? ''));
        lab_evidence($pdo, $who, 'staff.order.status', 'order', (string) (int) ($body['order_id'] ?? 0), $result['ok'] ? 'allowed' : 'denied', ['status' => $body['status'] ?? null]);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason']], 400);
        }
        lab_json($result);
        break;

    case $path === '/staff/evidence' && $method === 'GET':
        $who = lab_require_actor($pdo);
        lab_require_role($who, 'staff');
        $limit = max(1, min(500, (int) ($_GET['limit'] ?? 100)));
        $rows = $pdo->query('SELECT * FROM evidence_events ORDER BY rowid DESC LIMIT ' . $limit)->fetchAll();
        lab_json(['count' => count($rows), 'events' => $rows]);
        break;

    default:
        lab_json(['error' => 'not_found', 'path' => $path], 404);
}
