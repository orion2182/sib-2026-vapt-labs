<?php
declare(strict_types=1);

/** PerpusDigital synthetic fixture. Deterministic and fictional. */

return static function (PDO $pdo): void {
    $pdo->exec('DROP TABLE IF EXISTS fines');
    $pdo->exec('DROP TABLE IF EXISTS loans');
    $pdo->exec('DROP TABLE IF EXISTS books');
    $pdo->exec('DROP TABLE IF EXISTS users');
    $pdo->exec('DROP TABLE IF EXISTS evidence_events');

    $pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        email TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        role TEXT NOT NULL CHECK (role IN ("member","staff")),
        password_hash TEXT NOT NULL
    )');
    $pdo->exec('CREATE TABLE books (
        id INTEGER PRIMARY KEY,
        sku TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL,
        author TEXT NOT NULL,
        visibility TEXT NOT NULL CHECK (visibility IN ("public","private")),
        metadata_note TEXT NOT NULL,
        shelf TEXT NOT NULL,
        copies INTEGER NOT NULL
    )');
    $pdo->exec('CREATE TABLE loans (
        id INTEGER PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id),
        book_id INTEGER NOT NULL REFERENCES books(id),
        borrowed_at TEXT NOT NULL,
        due_at TEXT NOT NULL,
        returned_at TEXT,
        status TEXT NOT NULL CHECK (status IN ("active","returned"))
    )');
    $pdo->exec('CREATE TABLE fines (
        id INTEGER PRIMARY KEY,
        loan_id INTEGER NOT NULL REFERENCES loans(id),
        user_id INTEGER NOT NULL REFERENCES users(id),
        amount_cents INTEGER NOT NULL,
        reason TEXT NOT NULL,
        status TEXT NOT NULL CHECK (status IN ("outstanding","resolved"))
    )');
    $pdo->exec('CREATE TABLE evidence_events (
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
    )');

    $memberPass = password_hash('LabPass!2026', PASSWORD_DEFAULT);
    $staffPass = password_hash('StaffPass!2026', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (id,email,name,role,password_hash) VALUES (?,?,?,?,?)');
    foreach ([
        [1, 'nabila@perpus.test', 'Nabila Rahma', 'member', $memberPass],
        [2, 'raka@perpus.test', 'Raka Pratama', 'member', $memberPass],
        [3, 'dewi@perpus.test', 'Dewi Lestari', 'staff', $staffPass],
    ] as $u) {
        $stmt->execute($u);
    }

    $books = [
        [101, 'PD-BK-101', 'Keamanan Aplikasi Web', 'Rani Sutanto', 'public', 'Edisi kampus 2024.', 'A-01', 4],
        [102, 'PD-BK-102', 'Forensik Digital Terapan', 'Yoga Mahendra', 'public', 'Dilengkapi studi kasus.', 'A-02', 3],
        [103, 'PD-BK-103', 'Tata Kelola Risiko TI', 'Maya Kusuma', 'public', 'Untuk mata kuliah GRC.', 'A-03', 5],
        [104, 'PD-BK-104', 'Jaringan Komputer', 'Fajar Nugroho', 'public', 'Edisi revisi.', 'B-01', 6],
        [105, 'PD-BK-105', 'Basis Data Lanjut', 'Sinta Halim', 'public', 'Latihan SQL.', 'B-02', 4],
        [106, 'PD-BK-106', 'Etika Profesi TI', 'Bayu Saputra', 'public', 'Modul ringkas.', 'B-03', 8],
        [107, 'PD-BK-107', 'Sistem Operasi', 'Lestari Widodo', 'public', 'Praktikum proses.', 'C-01', 5],
        [108, 'PD-BK-108', 'Pemrograman PHP', 'Arif Budiman', 'public', 'Dasar hingga API.', 'C-02', 6],
        [109, 'PD-BK-109', 'Analisis Malware', 'Nadia Puspita', 'public', 'Hanya teori, tanpa sampel.', 'C-03', 2],
        [110, 'PD-BK-110', 'Privasi Data', 'Hendra Wijaya', 'public', 'Rujukan UU PDP.', 'D-01', 4],
        [111, 'PD-BK-111', 'Kontinuitas Bisnis', 'Rina Marlina', 'public', 'Latihan BIA.', 'D-02', 3],
        [112, 'PD-BK-112', 'Audit Sistem Informasi', 'Teguh Santoso', 'public', 'Kerangka audit.', 'D-03', 3],
        [9901, 'PD-ARK-9901', 'ARKIB-INTERNAL-0001', 'Unit Arsip', 'private', 'PD-MARKER-8K3L', 'Z-99', 1],
    ];
    $stmt = $pdo->prepare('INSERT INTO books (id,sku,title,author,visibility,metadata_note,shelf,copies) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($books as $b) {
        $stmt->execute($b);
    }

    $stmt = $pdo->prepare('INSERT INTO loans (id,user_id,book_id,borrowed_at,due_at,returned_at,status) VALUES (?,?,?,?,?,?,?)');
    $loans = [
        [6001, 1, 101, '2026-10-20T09:00:00Z', '2026-11-03T09:00:00Z', null, 'active'],
        [6002, 2, 102, '2026-10-01T10:00:00Z', '2026-10-15T10:00:00Z', '2026-10-20T11:00:00Z', 'returned'],
        [6003, 1, 104, '2026-11-01T08:30:00Z', '2026-11-15T08:30:00Z', null, 'active'],
    ];
    foreach ($loans as $l) {
        $stmt->execute($l);
    }

    $stmt = $pdo->prepare('INSERT INTO fines (id,loan_id,user_id,amount_cents,reason,status) VALUES (?,?,?,?,?,?)');
    $fines = [
        [7001, 6001, 1, 15000, 'Terlambat 7 hari', 'outstanding'],
        [7002, 6002, 2, 5000, 'Terlambat 5 hari', 'outstanding'],
    ];
    foreach ($fines as $f) {
        $stmt->execute($f);
    }
};
