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

function ep_wants_json(): bool
{
    if (($_GET['format'] ?? '') === 'html') {
        return false;
    }
    return ($_GET['format'] ?? '') === 'json'
        || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

function ep_redirect(string $to): void
{
    header('Location: ' . $to, true, 302);
    exit;
}

switch (true) {
    case $path === '/healthz' && $method === 'GET':
        $ok = true;
        try {
            $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
        } catch (Throwable $e) {
            $ok = false;
        }
        lab_json([
            'status' => $ok ? 'ok' : 'degraded',
            'target' => lab_target(),
            'variant' => $variant,
            'build_id' => lab_build()['build_id'] ?? null,
            'harness_version' => LAB_HARNESS_VERSION,
            'schema_version' => 'eventpass-1',
            'checks' => ['database' => $ok ? 'ok' : 'fail'],
            'request_id' => lab_request_id(),
        ]);
        break;

    case $path === '/' && $method === 'GET':
        ep_redirect('/events');

    case $path === '/events' && $method === 'GET':
        $events = ep_events($pdo);
        if (ep_wants_json()) {
            lab_json(['events' => $events]);
        }
        $cards = '';
        foreach ($events as $e) {
            $cards .= '<article class="card"><h2><a href="/event?id=' . (int) $e['id'] . '">' . lab_e($e['title']) . '</a></h2>'
                . '<p class="meta">' . lab_e($e['starts_at']) . ' · ' . lab_e($e['venue']) . '</p>'
                . '<p>' . lab_e($e['description']) . '</p>'
                . '<form method="post" action="/tickets/create" class="inline"><input type="hidden" name="event_id" value="' . (int) $e['id'] . '">'
                . '<button type="submit">Ambil tiket</button></form></article>';
        }
        ep_layout('Acara', '<h1>Acara</h1><section class="grid">' . $cards . '</section>', $actor);
        break;

    case $path === '/event' && $method === 'GET':
        $event = ep_event($pdo, (int) ($_GET['id'] ?? 0));
        if (!$event) {
            lab_json(['error' => 'event_not_found'], 404);
        }
        $reviews = ep_reviews($pdo, (int) $event['id']);
        if (ep_wants_json()) {
            lab_json(['event' => $event, 'reviews' => $reviews]);
        }
        $reviewHtml = '';
        foreach ($reviews as $review) {
            // Vulnerable build renders stored review text without context encoding.
            $body = $variant === 'vulnerable' ? $review['body'] : lab_e($review['body']);
            $reviewHtml .= '<article class="review"><p class="author">' . lab_e($review['author']) . '</p>'
                . '<div class="body">' . $body . '</div></article>';
        }
        ep_layout((string) $event['title'], '<h1>' . lab_e($event['title']) . '</h1>'
            . '<p class="meta">' . lab_e($event['starts_at']) . ' · ' . lab_e($event['venue']) . '</p>'
            . '<p>' . lab_e($event['description']) . '</p>'
            . ($actor ? '<form method="post" action="/reviews" class="stack"><input type="hidden" name="event_id" value="' . (int) $event['id'] . '">'
                . '<label>Tulis ulasan <textarea name="body" rows="3" maxlength="500"></textarea></label>'
                . '<button type="submit">Kirim ulasan</button></form>' : '')
            . '<h2>Ulasan</h2><section class="reviews">' . ($reviewHtml ?: '<p>Belum ada ulasan.</p>') . '</section>', $actor);
        break;

    case $path === '/login' && $method === 'GET':
        ep_layout('Masuk', '<h1>Masuk</h1><form method="post" action="/login" class="stack">'
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
        if (ep_wants_json()) {
            lab_json(['user' => $user]);
        }
        ep_redirect('/tickets');
        break;

    case $path === '/logout' && $method === 'POST':
        lab_logout($pdo);
        ep_redirect('/events');
        break;

    case $path === '/tickets' && $method === 'GET':
        $who = lab_require_actor($pdo);
        $tickets = ep_tickets_for($pdo, (int) $who['id']);
        if (ep_wants_json()) {
            lab_json(['tickets' => $tickets]);
        }
        $rows = '';
        foreach ($tickets as $t) {
            $rows .= '<tr><td>' . (int) $t['id'] . '</td><td>' . lab_e($t['event_title']) . '</td><td>' . lab_e($t['code'])
                . '</td><td>' . lab_e($t['display_name']) . '</td><td>' . lab_e($t['tier']) . '</td><td>'
                . ep_money((int) $t['price_cents']) . '</td><td>' . lab_e($t['status']) . '</td></tr>';
        }
        ep_layout('Tiket', '<h1>Tiket saya</h1><table><thead><tr><th>ID</th><th>Acara</th><th>Kode</th><th>Nama</th><th>Tier</th><th>Harga</th><th>Status</th></tr></thead><tbody>'
            . ($rows ?: '<tr><td colspan="7">Belum ada tiket.</td></tr>') . '</tbody></table>', $who);
        break;

    case $path === '/tickets/create' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $result = ep_create_ticket($pdo, $who, (int) ($body['event_id'] ?? 0));
        lab_evidence($pdo, $who, 'ticket.create', 'event', (string) (int) ($body['event_id'] ?? 0), $result['ok'] ? 'allowed' : 'denied', ['reason' => $result['reason'] ?? null]);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason']], $result['status']);
        }
        if (ep_wants_json()) {
            lab_json($result, 201);
        }
        ep_redirect('/tickets');
        break;

    case $path === '/tickets/update' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $ticketId = (int) ($body['ticket_id'] ?? 0);
        $fields = $body;
        unset($fields['ticket_id']);
        $result = ep_update_ticket($pdo, $who, $ticketId, $fields, $variant);
        lab_evidence($pdo, $who, 'ticket.update', 'ticket', (string) $ticketId, $result['ok'] ? 'allowed' : 'denied', [
            'reason' => $result['reason'] ?? null,
            'fields' => array_keys($fields),
        ]);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason'], 'field' => $result['field'] ?? null, 'request_id' => lab_request_id()], $result['status']);
        }
        lab_json(['ok' => true, 'ticket' => ep_ticket($pdo, $ticketId), 'request_id' => lab_request_id()]);
        break;

    case $path === '/reviews' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $eventId = (int) ($body['event_id'] ?? 0);
        $text = trim((string) ($body['body'] ?? ''));
        if (!ep_event($pdo, $eventId)) {
            lab_json(['error' => 'event_not_found'], 404);
        }
        if ($text === '' || mb_strlen($text) > 500) {
            lab_json(['error' => 'invalid_review'], 400);
        }
        $pdo->prepare('INSERT INTO reviews (event_id,user_id,body,created_at) VALUES (?,?,?,?)')
            ->execute([$eventId, (int) $who['id'], $text, lab_now()]);
        $reviewId = (int) $pdo->lastInsertId();
        lab_evidence($pdo, $who, 'review.create', 'review', (string) $reviewId, 'allowed', ['event_id' => $eventId, 'length' => mb_strlen($text)]);
        lab_json(['ok' => true, 'review_id' => $reviewId, 'request_id' => lab_request_id()], 201);
        break;

    case $path === '/checkin' && $method === 'POST':
        $who = lab_require_actor($pdo);
        $body = lab_body();
        $code = (string) ($body['ticket_code'] ?? '');
        $result = ep_checkin($pdo, $who, $code, $variant);
        lab_evidence($pdo, $who, 'checkin.record', 'ticket', substr($code, 0, 40), $result['ok'] ? 'allowed' : 'denied', [
            'reason' => $result['reason'] ?? null,
            'checkin_count' => $result['checkin_count'] ?? null,
        ]);
        if (!$result['ok']) {
            lab_json(['error' => $result['reason'], 'request_id' => lab_request_id()], $result['status']);
        }
        lab_json($result + ['request_id' => lab_request_id()]);
        break;

    case $path === '/organizer/event' && $method === 'GET':
        $who = lab_require_actor($pdo);
        lab_require_role($who, 'organizer');
        $eventId = (int) ($_GET['id'] ?? 0);
        if (!ep_event($pdo, $eventId)) {
            lab_json(['error' => 'event_not_found'], 404);
        }
        lab_json(['event_id' => $eventId, 'report' => ep_report($pdo, $eventId)]);
        break;

    case $path === '/organizer/evidence' && $method === 'GET':
        $who = lab_require_actor($pdo);
        lab_require_role($who, 'organizer');
        $limit = max(1, min(500, (int) ($_GET['limit'] ?? 100)));
        lab_json(['events' => $pdo->query('SELECT * FROM evidence_events ORDER BY rowid DESC LIMIT ' . $limit)->fetchAll()]);
        break;

    default:
        lab_json(['error' => 'not_found', 'path' => $path], 404);
}
