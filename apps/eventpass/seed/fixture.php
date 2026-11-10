<?php
declare(strict_types=1);

/** EventPass synthetic fixture. Deterministic and fictional. */

return static function (PDO $pdo): void {
    $pdo->exec('DROP TABLE IF EXISTS checkins');
    $pdo->exec('DROP TABLE IF EXISTS reviews');
    $pdo->exec('DROP TABLE IF EXISTS tickets');
    $pdo->exec('DROP TABLE IF EXISTS events');
    $pdo->exec('DROP TABLE IF EXISTS users');
    $pdo->exec('DROP TABLE IF EXISTS evidence_events');

    $pdo->exec('CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        email TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL,
        role TEXT NOT NULL CHECK (role IN ("attendee","organizer")),
        password_hash TEXT NOT NULL
    )');
    $pdo->exec('CREATE TABLE events (
        id INTEGER PRIMARY KEY,
        slug TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        capacity INTEGER NOT NULL,
        starts_at TEXT NOT NULL,
        venue TEXT NOT NULL
    )');
    $pdo->exec('CREATE TABLE tickets (
        id INTEGER PRIMARY KEY,
        event_id INTEGER NOT NULL REFERENCES events(id),
        user_id INTEGER NOT NULL REFERENCES users(id),
        code TEXT NOT NULL UNIQUE,
        display_name TEXT NOT NULL,
        tier TEXT NOT NULL CHECK (tier IN ("standard","vip")),
        price_cents INTEGER NOT NULL,
        status TEXT NOT NULL CHECK (status IN ("valid","checked_in")),
        created_at TEXT NOT NULL
    )');
    $pdo->exec('CREATE TABLE reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(id),
        user_id INTEGER NOT NULL REFERENCES users(id),
        body TEXT NOT NULL,
        created_at TEXT NOT NULL
    )');
    $pdo->exec('CREATE TABLE checkins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER NOT NULL REFERENCES tickets(id),
        checked_in_by INTEGER NOT NULL REFERENCES users(id),
        result TEXT NOT NULL,
        created_at TEXT NOT NULL
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

    $attendeePass = password_hash('LabPass!2026', PASSWORD_DEFAULT);
    $organizerPass = password_hash('StaffPass!2026', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (id,email,name,role,password_hash) VALUES (?,?,?,?,?)');
    foreach ([
        [1, 'laras@eventpass.test', 'Laras Ayu', 'attendee', $attendeePass],
        [2, 'dimas@eventpass.test', 'Dimas Prakoso', 'attendee', $attendeePass],
        [3, 'panitia@eventpass.test', 'Panitia Acara', 'organizer', $organizerPass],
    ] as $u) {
        $stmt->execute($u);
    }

    $stmt = $pdo->prepare('INSERT INTO events (id,slug,title,description,capacity,starts_at,venue) VALUES (?,?,?,?,?,?,?)');
    foreach ([
        [1, 'festival-inovasi', 'Festival Inovasi Kampus', 'Pameran proyek mahasiswa dan talkshow.', 100, '2026-12-05T09:00:00+07:00', 'Aula Utama'],
        [2, 'seminar-keamanan', 'Seminar Keamanan Siber', 'Diskusi tren keamanan dan karier.', 80, '2026-12-06T13:00:00+07:00', 'Gedung B'],
        [3, 'konser-malam', 'Konser Malam Kampus', 'Konser musik mahasiswa.', 200, '2026-12-10T19:00:00+07:00', 'Lapangan Timur'],
        [4, 'workshop-data', 'Workshop Analisis Data', 'Workshop praktik analisis data.', 40, '2026-12-12T10:00:00+07:00', 'Lab Komputer'],
    ] as $e) {
        $stmt->execute($e);
    }

    $stmt = $pdo->prepare('INSERT INTO tickets (id,event_id,user_id,code,display_name,tier,price_cents,status,created_at) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([8001, 1, 2, 'EP-1002', 'Dimas Prakoso', 'standard', 5000000, 'valid', '2026-11-04T10:00:00Z']);
    $stmt->execute([8002, 2, 2, 'EP-1003', 'Dimas Prakoso', 'standard', 3500000, 'valid', '2026-11-04T10:05:00Z']);

    $stmt = $pdo->prepare('INSERT INTO reviews (event_id,user_id,body,created_at) VALUES (?,?,?,?)');
    $stmt->execute([1, 2, 'Acara tahun lalu cukup rapi.', '2026-11-04T10:10:00Z']);
};
