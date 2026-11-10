<?php
declare(strict_types=1);

/** KampusMart domain logic. Intentionally small and readable. */

function km_money(int $cents): string
{
    return 'Rp ' . number_format($cents / 100, 0, ',', '.');
}

function km_products(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM products ORDER BY id')->fetchAll();
}

function km_product(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function km_next_id(PDO $pdo, string $table): int
{
    $allowed = ['orders', 'invoices'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('bad table');
    }
    return (int) $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM ' . $table)->fetchColumn();
}

function km_order(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function km_order_items(PDO $pdo, int $orderId): array
{
    $stmt = $pdo->prepare(
        'SELECT oi.*, p.name, p.sku FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id'
    );
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

function km_orders_for_user(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT o.*, i.id AS invoice_id, i.number AS invoice_number
         FROM orders o LEFT JOIN invoices i ON i.order_id = o.id
         WHERE o.user_id = ? ORDER BY o.created_at DESC, o.id DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function km_all_orders(PDO $pdo): array
{
    return $pdo->query(
        'SELECT o.*, u.email AS customer_email, i.number AS invoice_number, i.id AS invoice_id
         FROM orders o JOIN users u ON u.id = o.user_id
         LEFT JOIN invoices i ON i.order_id = o.id
         ORDER BY o.created_at DESC, o.id DESC'
    )->fetchAll();
}

function km_invoice(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT i.*, o.subtotal_cents, o.discount_cents, o.total_cents, o.status, u.email AS owner_email
         FROM invoices i JOIN orders o ON o.id = i.order_id JOIN users u ON u.id = i.user_id
         WHERE i.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Invoice read with the ownership decision separated from the fetch so the
 * vulnerable and patched builds differ in exactly one place.
 */
function km_invoice_for(PDO $pdo, array $actor, int $id, string $variant): array
{
    $invoice = km_invoice($pdo, $id);
    if (!$invoice) {
        return ['ok' => false, 'status' => 404, 'reason' => 'invoice_not_found'];
    }
    if ($variant !== 'vulnerable') {
        if ((int) $invoice['user_id'] !== (int) $actor['id'] && ($actor['role'] ?? '') !== 'staff') {
            return ['ok' => false, 'status' => 403, 'reason' => 'not_invoice_owner'];
        }
    }
    return ['ok' => true, 'status' => 200, 'invoice' => $invoice];
}

function km_coupon(PDO $pdo, string $code): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = ?');
    $stmt->execute([strtoupper(trim($code))]);
    return $stmt->fetch() ?: null;
}

function km_create_order(PDO $pdo, array $user, array $cart): array
{
    if ($cart === []) {
        return ['ok' => false, 'reason' => 'cart_empty'];
    }
    $pdo->beginTransaction();
    try {
        $lines = [];
        $subtotal = 0;
        foreach ($cart as $productId => $qty) {
            $product = km_product($pdo, (int) $productId);
            $qty = max(1, min(99, (int) $qty));
            if (!$product) {
                $pdo->rollBack();
                return ['ok' => false, 'reason' => 'unknown_product'];
            }
            if ((int) $product['stock'] < $qty) {
                $pdo->rollBack();
                return ['ok' => false, 'reason' => 'insufficient_stock', 'product' => $product['sku']];
            }
            $subtotal += (int) $product['price_cents'] * $qty;
            $lines[] = [$product, $qty];
        }

        $orderId = km_next_id($pdo, 'orders');
        $created = lab_now();
        $pdo->prepare(
            'INSERT INTO orders (id, user_id, status, subtotal_cents, discount_cents, total_cents, created_at) VALUES (?, ?, ?, ?, 0, ?, ?)'
        )->execute([$orderId, (int) $user['id'], 'pending', $subtotal, $subtotal, $created]);

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty, unit_price_cents) VALUES (?, ?, ?, ?)');
        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
        foreach ($lines as [$product, $qty]) {
            $itemStmt->execute([$orderId, (int) $product['id'], $qty, (int) $product['price_cents']]);
            $stockStmt->execute([$qty, (int) $product['id']]);
        }

        $invoiceId = km_next_id($pdo, 'invoices');
        $marker = 'KM-AUTO-' . strtoupper(substr(hash('sha256', $invoiceId . '|' . $user['id'] . '|kampusmart'), 0, 8));
        $pdo->prepare(
            'INSERT INTO invoices (id, order_id, user_id, number, marker, issued_at) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $invoiceId,
            $orderId,
            (int) $user['id'],
            'INV-KM-' . $user['id'] . '-' . (1000 + $orderId),
            $marker,
            $created,
        ]);

        $pdo->commit();
        return ['ok' => true, 'order_id' => $orderId, 'invoice_id' => $invoiceId, 'total_cents' => $subtotal];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'reason' => 'order_failed'];
    }
}

/**
 * Apply a coupon to an owned order.
 *
 * The vulnerable build ignores the one-use rule. The patched build enforces
 * both the coupon counter and per-order uniqueness atomically.
 */
function km_apply_coupon(PDO $pdo, array $actor, string $code, int $orderId, string $variant): array
{
    $coupon = km_coupon($pdo, $code);
    if (!$coupon || (int) $coupon['active'] !== 1) {
        return ['ok' => false, 'status' => 400, 'reason' => 'coupon_invalid'];
    }
    $order = km_order($pdo, $orderId);
    if (!$order) {
        return ['ok' => false, 'status' => 404, 'reason' => 'order_not_found'];
    }
    if ((int) $order['user_id'] !== (int) $actor['id'] && ($actor['role'] ?? '') !== 'staff') {
        return ['ok' => false, 'status' => 403, 'reason' => 'not_order_owner'];
    }
    if (!in_array($order['status'], ['pending'], true)) {
        return ['ok' => false, 'status' => 409, 'reason' => 'order_not_pending'];
    }

    $percent = (int) $coupon['percent_off'];
    $increment = (int) round(((int) $order['subtotal_cents']) * $percent / 100);

    $pdo->beginTransaction();
    try {
        if ($variant === 'vulnerable') {
            // Deliberate flaw: no max_uses check and no per-order uniqueness.
            $pdo->prepare('UPDATE coupons SET uses = uses + 1 WHERE id = ?')->execute([(int) $coupon['id']]);
        } else {
            $update = $pdo->prepare('UPDATE coupons SET uses = uses + 1 WHERE id = ? AND active = 1 AND uses < max_uses');
            $update->execute([(int) $coupon['id']]);
            if ($update->rowCount() !== 1) {
                $pdo->rollBack();
                return ['ok' => false, 'status' => 409, 'reason' => 'coupon_exhausted'];
            }
            $exists = $pdo->prepare('SELECT 1 FROM coupon_redemptions WHERE coupon_id = ? AND order_id = ?');
            $exists->execute([(int) $coupon['id'], $orderId]);
            if ($exists->fetchColumn()) {
                $pdo->rollBack();
                return ['ok' => false, 'status' => 409, 'reason' => 'coupon_already_applied'];
            }
        }

        $pdo->prepare(
            'INSERT INTO coupon_redemptions (coupon_id, order_id, user_id, created_at) VALUES (?, ?, ?, ?)'
        )->execute([(int) $coupon['id'], $orderId, (int) $actor['id'], lab_now()]);

        $newDiscount = min((int) $order['subtotal_cents'], (int) $order['discount_cents'] + $increment);
        $newTotal = (int) $order['subtotal_cents'] - $newDiscount;
        $pdo->prepare('UPDATE orders SET discount_cents = ?, total_cents = ? WHERE id = ?')
            ->execute([$newDiscount, $newTotal, $orderId]);

        $uses = (int) $pdo->query('SELECT uses FROM coupons WHERE id = ' . (int) $coupon['id'])->fetchColumn();
        $pdo->commit();
        return [
            'ok' => true,
            'status' => 200,
            'discount_cents' => $newDiscount,
            'total_cents' => $newTotal,
            'coupon_uses' => $uses,
            'max_uses' => (int) $coupon['max_uses'],
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'status' => 500, 'reason' => 'coupon_apply_failed'];
    }
}

/**
 * Legacy document service. The vulnerable build joins the requested name onto
 * the public directory without normalising it, so one sibling synthetic marker
 * is reachable. The patched build confines requests to the public directory.
 */
function km_legacy_read(string $name, string $variant): array
{
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'svc' . DIRECTORY_SEPARATOR . 'public';
    $target = $base . DIRECTORY_SEPARATOR . $name;

    if ($variant !== 'vulnerable') {
        $realBase = realpath($base);
        $realTarget = realpath($target);
        if ($realBase === false || $realTarget === false) {
            return ['ok' => false, 'status' => 404, 'reason' => 'document_not_found'];
        }
        if ($realTarget !== $realBase && !str_starts_with($realTarget, $realBase . DIRECTORY_SEPARATOR)) {
            return ['ok' => false, 'status' => 403, 'reason' => 'path_outside_public_root'];
        }
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
            return ['ok' => false, 'status' => 403, 'reason' => 'invalid_document_name'];
        }
    }

    if (!is_file($target)) {
        return ['ok' => false, 'status' => 404, 'reason' => 'document_not_found'];
    }
    return [
        'ok' => true,
        'status' => 200,
        'name' => $name,
        'bytes' => (int) filesize($target),
        'sha256' => hash_file('sha256', $target),
        'content' => (string) file_get_contents($target),
    ];
}

function km_update_status(PDO $pdo, int $orderId, string $status): array
{
    if (!in_array($status, ['pending', 'paid', 'shipped', 'cancelled'], true)) {
        return ['ok' => false, 'reason' => 'invalid_status'];
    }
    $order = km_order($pdo, $orderId);
    if (!$order) {
        return ['ok' => false, 'reason' => 'order_not_found'];
    }
    $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
    return ['ok' => true, 'order_id' => $orderId, 'status' => $status];
}

function km_layout(string $title, string $body, ?array $actor): void
{
    header('Content-Type: text/html; charset=utf-8');
    $nav = $actor
        ? '<span class="who">' . lab_e($actor['name']) . ' (' . lab_e($actor['role']) . ')</span>
           <form method="post" action="/logout" class="inline"><button type="submit">Keluar</button></form>'
        : '<a href="/login">Masuk</a>';
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . lab_e($title) . ' — KampusMart</title>'
        . '<link rel="stylesheet" href="/assets/style.css"></head><body>'
        . '<header class="top"><a class="brand" href="/">KampusMart</a>'
        . '<nav><a href="/catalog">Katalog</a><a href="/cart">Keranjang</a>'
        . '<a href="/orders">Pesanan</a>' . $nav . '</nav></header>'
        . '<main>' . $body . '</main>'
        . '<footer>Lab sintetis SIB 2026 · build ' . lab_e((string) (lab_build()['build_id'] ?? 'dev'))
        . ' · <span class="variant">' . lab_e(lab_variant()) . '</span></footer>'
        . '</body></html>';
}
