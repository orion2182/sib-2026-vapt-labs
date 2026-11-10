<?php
declare(strict_types=1);

/**
 * KampusMart synthetic fixture.
 *
 * All identities, products, orders, invoices, and markers are fictional.
 * The fixture is deterministic: reset always returns the same IDs and markers.
 */

return static function (PDO $pdo): void {
    $pdo->exec('DROP TABLE IF EXISTS coupon_redemptions');
    $pdo->exec('DROP TABLE IF EXISTS coupons');
    $pdo->exec('DROP TABLE IF EXISTS invoices');
    $pdo->exec('DROP TABLE IF EXISTS order_items');
    $pdo->exec('DROP TABLE IF EXISTS orders');
    $pdo->exec('DROP TABLE IF EXISTS products');
    $pdo->exec('DROP TABLE IF EXISTS users');
    $pdo->exec('DROP TABLE IF EXISTS evidence_events');

    $pdo->exec(
        'CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            email TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            role TEXT NOT NULL CHECK (role IN ("customer", "staff")),
            password_hash TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            sku TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            description TEXT NOT NULL,
            price_cents INTEGER NOT NULL,
            stock INTEGER NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            status TEXT NOT NULL CHECK (status IN ("pending", "paid", "shipped", "cancelled")),
            subtotal_cents INTEGER NOT NULL,
            discount_cents INTEGER NOT NULL DEFAULT 0,
            total_cents INTEGER NOT NULL,
            created_at TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL REFERENCES orders(id),
            product_id INTEGER NOT NULL REFERENCES products(id),
            qty INTEGER NOT NULL,
            unit_price_cents INTEGER NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE invoices (
            id INTEGER PRIMARY KEY,
            order_id INTEGER NOT NULL REFERENCES orders(id),
            user_id INTEGER NOT NULL REFERENCES users(id),
            number TEXT NOT NULL UNIQUE,
            marker TEXT NOT NULL,
            issued_at TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE coupons (
            id INTEGER PRIMARY KEY,
            code TEXT NOT NULL UNIQUE,
            percent_off INTEGER NOT NULL,
            max_uses INTEGER NOT NULL,
            uses INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1
        )'
    );
    $pdo->exec(
        'CREATE TABLE coupon_redemptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            coupon_id INTEGER NOT NULL REFERENCES coupons(id),
            order_id INTEGER NOT NULL REFERENCES orders(id),
            user_id INTEGER NOT NULL REFERENCES users(id),
            created_at TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE evidence_events (
            id TEXT PRIMARY KEY,
            request_id TEXT NOT NULL,
            ts_utc TEXT NOT NULL,
            actor TEXT NOT NULL,
            role TEXT NOT NULL,
            action TEXT NOT NULL,
            object_type TEXT NOT NULL,
            object_id TEXT NOT NULL,
            result TEXT NOT NULL,
            detail TEXT NOT NULL
        )'
    );

    $password = password_hash('LabPass!2026', PASSWORD_DEFAULT);
    $staffPassword = password_hash('StaffPass!2026', PASSWORD_DEFAULT);
    $users = [
        [1, 'aurel@kampusmart.test', 'Aurel Pradita', 'customer', $password],
        [2, 'bima@kampusmart.test', 'Bima Nugraha', 'customer', $password],
        [3, 'sari@kampusmart.test', 'Sari Wulandari', 'staff', $staffPassword],
    ];
    $stmt = $pdo->prepare('INSERT INTO users (id, email, name, role, password_hash) VALUES (?, ?, ?, ?, ?)');
    foreach ($users as $u) {
        $stmt->execute($u);
    }

    $products = [
        [101, 'KM-TSH-01', 'Kaos Kampus Biru', 'Kaos katun biru dengan logo kampus fiktif.', 8500000, 40],
        [102, 'KM-HOD-02', 'Hoodie Malam', 'Hoodie abu-abu, edisi sintetis.', 17500000, 18],
        [103, 'KM-MUG-03', 'Mug Kopi Kampus', 'Mug keramik 350 ml.', 6500000, 60],
        [104, 'KM-NSB-04', 'Notebook A5', 'Buku catatan 160 halaman.', 4500000, 75],
        [105, 'KM-PEN-05', 'Pulpen Gel Hitam', 'Pulpen gel 0.5 mm.', 1200000, 200],
        [106, 'KM-BAG-06', 'Tas Kanvas', 'Tas kanvas dengan kompartemen laptop.', 14500000, 22],
        [107, 'KM-BTL-07', 'Botol Minum', 'Botol stainless 600 ml.', 9500000, 35],
        [108, 'KM-STK-08', 'Stiker Set', 'Delapan stiker ilustrasi kampus.', 2000000, 120],
        [109, 'KM-JKT-09', 'Jaket Almamater', 'Jaket sintetis untuk kegiatan kampus.', 25000000, 12],
        [110, 'KM-CAP-10', 'Topi Kampus', 'Topi adjustable.', 5500000, 50],
        [111, 'KM-LMP-11', 'Lampu Meja', 'Lampu meja LED kecil.', 11000000, 25],
        [112, 'KM-CHR-12', 'Kursi Lipat', 'Kursi lipat ringan.', 19000000, 15],
    ];
    $stmt = $pdo->prepare('INSERT INTO products (id, sku, name, description, price_cents, stock) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($products as $p) {
        $stmt->execute($p);
    }

    $orders = [
        [5001, 1, 'paid', 17500000, 0, 17500000, '2026-11-02T09:15:00Z'],
        [5002, 2, 'paid', 14500000, 0, 14500000, '2026-11-03T14:40:00Z'],
    ];
    $stmt = $pdo->prepare('INSERT INTO orders (id, user_id, status, subtotal_cents, discount_cents, total_cents, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($orders as $o) {
        $stmt->execute($o);
    }
    $items = [
        [5001, 102, 1, 17500000],
        [5002, 106, 1, 14500000],
    ];
    $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, qty, unit_price_cents) VALUES (?, ?, ?, ?)');
    foreach ($items as $i) {
        $stmt->execute($i);
    }

    $invoices = [
        [7001, 5001, 1, 'INV-KM-A-1001', 'KM-A-INVOICE-7Q2', '2026-11-02T09:16:00Z'],
        [7002, 5002, 2, 'INV-KM-B-1002', 'KM-B-INVOICE-9X4', '2026-11-03T14:41:00Z'],
    ];
    $stmt = $pdo->prepare('INSERT INTO invoices (id, order_id, user_id, number, marker, issued_at) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($invoices as $inv) {
        $stmt->execute($inv);
    }

    $coupons = [
        [9001, 'HEMAT10', 10, 1, 0, 1],
        [9002, 'WELCOME5', 5, 5, 0, 1],
        [9003, 'EXPIRED25', 25, 1, 0, 0],
    ];
    $stmt = $pdo->prepare('INSERT INTO coupons (id, code, percent_off, max_uses, uses, active) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($coupons as $c) {
        $stmt->execute($c);
    }
};
