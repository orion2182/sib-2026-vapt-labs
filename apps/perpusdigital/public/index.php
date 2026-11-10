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

function pd_wants_json(): bool
{
    return ($_GET['format'] ?? '') === 'json'
        || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

function pd_page(string $title, string $body, ?array $actor): void
{
    if (pd_wants_json()) {
        lab_json(['error' => 'html_only']);
    }
    pd_layout($title, $body, $actor);
    exit;
}

function pd_redirect(string $to): void
{
    header('Location: ' . $to, true, 302);
    exit;
}

switch (true) {
    case $path === '/healthz' && $method === 'GET':
        $ok = true;
        try {
            $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
        } catch (Throwable $e) {
            $ok = false;
        }
        lab_json([
            'status' => $ok ? 'ok' : 'degraded',
            'target' => lab_target(),
            'variant' => $variant,
            'build_id' => lab_build()['build_id'] ?? null,
            'harness_version' => LAB_HARNESS_VERSION,
            'schema_version' => 'perpusdigital-1',
            'checks' => ['database' => $ok ? 'ok' : 'fail'],
            'request_id' => lab_request_id(),
        ]);
        break;

    case $path === '/' && $method === 'GET':
        pd_redirect('/catalog');

    case $path === '/catalog' && $method === 'GET':
        $term = (string) ($_GET['q'] ?? '');
        if ($term !== '') {
            try {
                $books = pd_search($pdo, $term, $variant);
            } catch (Throwable $e) {
                lab_evidence($pdo, $actor, 'catalog.search', 'query', substr($term, 0, 80), 'error', ['error' => 'query_failed']);
                lab_json(['error' => 'query_failed', 'request_id' => lab_request_id()], 400);
            }
            $private = 0;
            foreach ($books as $b) {
                if (($b['visibility'] ?? 'public') === 'private') {
                    $private++;
                }
            }
            lab_evidence($pdo, $actor, 'catalog.search', 'query', substr($term, 0, 80), 'allowed', [
                'results' => count($books),
            ]);
        } else {
            $books = $pdo->query("SELECT id,sku,title,author,shelf,copies,metadata_note FROM books WHERE visibility='public' ORDER BY title")->fetchAll();
        }
        if (pd_wants_json()) {
            lab_json(['query' => $term, 'count' => count($books), 'books' => $books]);
        }
        $cards = '';
        foreach ($books as $b) {
            $cards .= '<article class="card"><h2><a href="/book?id=' . (int) $b['id'] . '">' . lab_e($b['title']) . '</a></h2>'
                . '<p class="meta">' . lab_e($b['author']) . ' · ' . lab_e($b['sku']) . '</p>'
                . '<p class="shelf">Rak ' . lab_e($b['shelf']) . ' · ' . (int) $b['copies'] . ' salinan</p>'
                . '<p class="note">' . lab_e($b['metadata_note']) . '</p>'
                . '<form method="post" action="/borrow" class="inline"><input type="hidden" name="book_id" value="' . (int) $b['id'] . '">'
                . '<button type="submit">Pinjam</button></form></article>';
        }
        pd_page('Katalog', '<h1>Katalog</h1><form method="get" action="/catalog" class="search">'
            . '<input name="q" value="' . lab_e($term) . '" placeholder="Cari judul atau penulis">'
            . '<button type="submit">Cari</button></form>'
            . '<p class="count">' . count($books) . ' hasil</p><section class="grid">' . ($cards ?: '<p>Tidak ada hasil.</p>') . '</section>', $actor);
        break;

    case $path === '/book' && $method === 'GET':
        $book = pd_book($pdo, (int) ($_GET['id'] ?? 0));
        if (!$book || ($book['visibility'] !== 'public' && ($actor['role'] ?? '') !== 'staff')) {
            lab_json(['error' => 'book_not_found'], 404);
        }
        if (pd_wants_json()) {
            lab_json(['book' => $book]);
        }
        pd_page($book['title'], '<h1>' . lab_e($book['title']) . '</h1><p class="meta">' . lab_e($book['author'])
            . ' · ' . lab_e($book['sku']) . '</p><p>Rak ' . lab_e($book['shelf']) . ' · stok ' . (int) $book['copies'] . '</p>'
            . '<p class="note">' . lab_e($book['metadata_note']) . '</p>', $actor);
        break;

    case $path === '/login' && $method === 'GET':
        pd_page('Masuk', '<h1>Masuk</h1><form method="post" action="/login" class="stack">'
            . '<label>Email <input type="email" name="email" required></label>'
            . '<label>Kata sandi <input type="password" name="password" required></label>'
            . '<button type="submit">Masuk</button></form>', $actor);
        break;

    case $path === '/login' && $method === 'POST':
        $body = lab_body();
        $user = lab_login($pdo, (string) ($body['email'] ?? ''), (string) ($body['password'] ?? ''));
        if (!$user) {
            lab_json(['error' => 'invalid_credentials', 'request_id' => lab_request_id()], 401);
        }
        if (pd_wants_json()) {
            lab_json(['user' => $user]);
        }
        pd_redirect('/loans');
        break;

    case $path === '/logout' && $method === 'POST':
        lab_logout($pdo);
        pd_redirect('/catalog');
        break;

    case $path === '/account' && $method === 'GET':
        $who = lab_require_actor($pdo);
        lab_json(['user' => $who, 'loans' => pd_loans_for($pdo, (int) $who['id']), 'fines' => pd_fines_for($pdo, (int) $who['id'])]);
        break;

    case $path === '/borrow' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $result = pd_borrow($pdo, $who, (int) ($body['book_id'] ?? 0));
        lab_evidence($pdo, $who, 'loan.borrow', 'book', (string) (int) ($body['book_id'] ?? 0), $result['ok'] ? 'allowed' : 'denied', ['reason' => $result['reason'] ?? null]);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason']], $result['status']);
        }
        if (pd_wants_json()) {
            lab_json($result, 201);
        }
        pd_redirect('/loans');
        break;

    case $path === '/return' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $result = pd_return($pdo, $who, (int) ($body['loan_id'] ?? 0));
        lab_evidence($pdo, $who, 'loan.return', 'loan', (string) (int) ($body['loan_id'] ?? 0), $result['ok'] ? 'allowed' : 'denied', ['reason' => $result['reason'] ?? null]);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason']], $result['status']);
        }
        lab_json($result);
        break;

    case $path === '/loans' && $method === 'GET':
        $who = lab_require_actor($pdo);
        $loans = pd_loans_for($pdo, (int) $who['id']);
        if (pd_wants_json()) {
            lab_json(['loans' => $loans]);
        }
        $rows = '';
        foreach ($loans as $l) {
            $rows .= '<tr><td>' . (int) $l['id'] . '</td><td>' . lab_e($l['title']) . '</td><td>' . lab_e($l['status'])
                . '</td><td>' . lab_e($l['due_at']) . '</td><td>'
                . ($l['status'] === 'active' ? '<form method="post" action="/return" class="inline"><input type="hidden" name="loan_id" value="' . (int) $l['id'] . '"><button type="submit">Kembalikan</button></form>' : '-')
                . '</td></tr>';
        }
        pd_page('Pinjaman', '<h1>Pinjaman saya</h1><table><thead><tr><th>ID</th><th>Buku</th><th>Status</th><th>Jatuh tempo</th><th>Aksi</th></tr></thead><tbody>'
            . ($rows ?: '<tr><td colspan="5">Belum ada pinjaman.</td></tr>') . '</tbody></table>', $who);
        break;

    case $path === '/fines' && $method === 'GET':
        $who = lab_require_actor($pdo);
        $fines = pd_fines_for($pdo, (int) $who['id']);
        if (pd_wants_json()) {
            lab_json(['fines' => $fines]);
        }
        $rows = '';
        foreach ($fines as $f) {
            $rows .= '<tr><td>' . (int) $f['id'] . '</td><td>' . lab_e($f['title']) . '</td><td>' . pd_money((int) $f['amount_cents'])
                . '</td><td>' . lab_e($f['loan_status']) . '</td><td>' . lab_e($f['status']) . '</td></tr>';
        }
        pd_page('Denda', '<h1>Denda</h1><table><thead><tr><th>ID</th><th>Buku</th><th>Jumlah</th><th>Status pinjaman</th><th>Status denda</th></tr></thead><tbody>'
            . ($rows ?: '<tr><td colspan="5">Tidak ada denda.</td></tr>') . '</tbody></table>', $who);
        break;

    case $path === '/fines/waive' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $fineId = (int) ($body['fine_id'] ?? 0);
        $result = pd_waive_fine($pdo, $who, $fineId, $variant);
        lab_evidence($pdo, $who, 'fine.waive', 'fine', (string) $fineId, $result['ok'] ? 'allowed' : 'denied', ['reason' => $result['reason'] ?? null]);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason'], 'request_id' => lab_request_id()], $result['status']);
        }
        lab_json(['ok' => true, 'fine_id' => $fineId, 'request_id' => lab_request_id()]);
        break;

    case $path === '/staff/book/update' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $bookId = (int) ($body['book_id'] ?? 0);
        $result = pd_update_book($pdo, $who, $bookId, $body, $variant);
        lab_evidence($pdo, $who, 'staff.book.update', 'book', (string) $bookId, $result['ok'] ? 'allowed' : 'denied', ['reason' => $result['reason'] ?? null]);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason'], 'request_id' => lab_request_id()], $result['status']);
        }
        lab_json(['ok' => true, 'book' => pd_book($pdo, $bookId), 'request_id' => lab_request_id()]);
        break;

    case $path === '/staff/books' && $method === 'GET':
        $who = lab_require_actor($pdo);
        lab_require_role($who, 'staff');
        $books = $pdo->query('SELECT * FROM books ORDER BY title')->fetchAll();
        if (pd_wants_json()) {
            lab_json(['books' => $books]);
        }
        $rows = '';
        foreach ($books as $b) {
            $rows .= '<tr><td>' . (int) $b['id'] . '</td><td>' . lab_e($b['sku']) . '</td><td>'
                . '<form method="post" action="/staff/book/update" class="inline">'
                . '<input type="hidden" name="book_id" value="' . (int) $b['id'] . '">'
                . '<input type="text" name="title" value="' . lab_e($b['title']) . '" maxlength="120">'
                . ' <input type="text" name="author" value="' . lab_e($b['author']) . '" maxlength="80">'
                . ' <input type="text" name="shelf" value="' . lab_e($b['shelf']) . '" maxlength="12">'
                . ' <button type="submit">Simpan</button></form></td>'
                . '<td>' . lab_e($b['visibility']) . '</td></tr>';
        }
        pd_page('Kelola buku', '<h1>Kelola metadata buku</h1>'
            . '<p class="count">Fungsi ini hanya ditampilkan untuk petugas. Uji apakah server menegakkan peran yang sama.</p>'
            . '<table><thead><tr><th>ID</th><th>SKU</th><th>Metadata</th><th>Visibilitas</th></tr></thead><tbody>'
            . $rows . '</tbody></table>', $who);
        break;

    case $path === '/staff/loans' && $method === 'GET':
        $who = lab_require_actor($pdo);
        lab_require_role($who, 'staff');
        lab_json(['loans' => pd_all_loans($pdo)]);
        break;

    case $path === '/staff/evidence' && $method === 'GET':
        $who = lab_require_actor($pdo);
        lab_require_role($who, 'staff');
        $limit = max(1, min(500, (int) ($_GET['limit'] ?? 100)));
        lab_json(['events' => $pdo->query('SELECT * FROM evidence_events ORDER BY rowid DESC LIMIT ' . $limit)->fetchAll()]);
        break;

    default:
        lab_json(['error' => 'not_found', 'path' => $path], 404);
}
