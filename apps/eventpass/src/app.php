<?php
declare(strict_types=1);

/** EventPass domain logic. */

function ep_money(int $cents): string
{
    return 'Rp ' . number_format($cents / 100, 0, ',', '.');
}

function ep_events(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM events ORDER BY starts_at')->fetchAll();
}

function ep_event(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function ep_tickets_for(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT t.*, e.title AS event_title FROM tickets t JOIN events e ON e.id = t.event_id
         WHERE t.user_id = ? ORDER BY t.created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function ep_ticket(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM tickets WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function ep_reviews(PDO $pdo, int $eventId): array
{
    $stmt = $pdo->prepare(
        'SELECT r.*, u.name AS author FROM reviews r JOIN users u ON u.id = r.user_id
         WHERE r.event_id = ? ORDER BY r.id DESC'
    );
    $stmt->execute([$eventId]);
    return $stmt->fetchAll();
}

function ep_create_ticket(PDO $pdo, array $actor, int $eventId): array
{
    $event = ep_event($pdo, $eventId);
    if (!$event) {
        return ['ok' => false, 'status' => 404, 'reason' => 'event_not_found'];
    }
    $sold = (int) $pdo->query('SELECT COUNT(*) FROM tickets WHERE event_id = ' . (int) $eventId)->fetchColumn();
    if ($sold >= (int) $event['capacity']) {
        return ['ok' => false, 'status' => 409, 'reason' => 'event_full'];
    }
    $id = (int) $pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM tickets')->fetchColumn();
    $code = 'EP-' . (1000 + $id);
    $pdo->prepare(
        'INSERT INTO tickets (id,event_id,user_id,code,display_name,tier,price_cents,status,created_at)
         VALUES (?,?,?,?,?,?,?,?,?)'
    )->execute([$id, $eventId, (int) $actor['id'], $code, (string) $actor['name'], 'standard', 5000000, 'valid', lab_now()]);
    return ['ok' => true, 'status' => 201, 'ticket_id' => $id, 'code' => $code];
}

/**
 * Ticket update. The vulnerable build accepts organizer-controlled fields from
 * an attendee. The patched build allows only attendee-owned display fields.
 */
function ep_update_ticket(PDO $pdo, array $actor, int $ticketId, array $fields, string $variant): array
{
    $ticket = ep_ticket($pdo, $ticketId);
    if (!$ticket) {
        return ['ok' => false, 'status' => 404, 'reason' => 'ticket_not_found'];
    }
    if ((int) $ticket['user_id'] !== (int) $actor['id'] && ($actor['role'] ?? '') !== 'organizer') {
        return ['ok' => false, 'status' => 403, 'reason' => 'not_ticket_owner'];
    }

    $organizerFields = ['tier', 'price_cents', 'status', 'event_id', 'user_id', 'code', 'id'];
    if ($variant !== 'vulnerable') {
        foreach ($organizerFields as $field) {
            if (array_key_exists($field, $fields)) {
                return ['ok' => false, 'status' => 403, 'reason' => 'field_not_allowed', 'field' => $field];
            }
        }
    }

    $displayName = isset($fields['display_name']) ? trim((string) $fields['display_name']) : $ticket['display_name'];
    if ($displayName === '' || mb_strlen($displayName) > 80) {
        return ['ok' => false, 'status' => 400, 'reason' => 'invalid_display_name'];
    }
    $tier = $ticket['tier'];
    $price = (int) $ticket['price_cents'];
    if ($variant === 'vulnerable') {
        if (isset($fields['tier']) && in_array($fields['tier'], ['standard', 'vip'], true)) {
            $tier = $fields['tier'];
        }
        if (isset($fields['price_cents']) && is_numeric($fields['price_cents'])) {
            $price = max(0, (int) $fields['price_cents']);
        }
    }
    $pdo->prepare('UPDATE tickets SET display_name = ?, tier = ?, price_cents = ? WHERE id = ?')
        ->execute([$displayName, $tier, $price, $ticketId]);
    return ['ok' => true, 'status' => 200, 'ticket_id' => $ticketId];
}

/**
 * Check-in. Both builds require an organizer actor. The vulnerable build writes
 * a new check-in row every time; the patched build performs a one-time atomic
 * transition from valid to checked_in.
 */
function ep_checkin(PDO $pdo, array $actor, string $code, string $variant): array
{
    if (($actor['role'] ?? '') !== 'organizer') {
        return ['ok' => false, 'status' => 403, 'reason' => 'organizer_role_required'];
    }
    $stmt = $pdo->prepare('SELECT * FROM tickets WHERE code = ?');
    $stmt->execute([strtoupper(trim($code))]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        return ['ok' => false, 'status' => 404, 'reason' => 'ticket_not_found'];
    }
    $now = lab_now();
    if ($variant === 'vulnerable') {
        $pdo->prepare('INSERT INTO checkins (ticket_id,checked_in_by,result,created_at) VALUES (?,?,?,?)')
            ->execute([(int) $ticket['id'], (int) $actor['id'], 'accepted', $now]);
        $pdo->prepare('UPDATE tickets SET status = ? WHERE id = ?')->execute(['checked_in', (int) $ticket['id']]);
        $count = (int) $pdo->query('SELECT COUNT(*) FROM checkins WHERE ticket_id = ' . (int) $ticket['id'])->fetchColumn();
        return ['ok' => true, 'status' => 200, 'ticket_id' => (int) $ticket['id'], 'checkin_count' => $count];
    }
    $pdo->beginTransaction();
    try {
        $update = $pdo->prepare('UPDATE tickets SET status = ? WHERE id = ? AND status = ?');
        $update->execute(['checked_in', (int) $ticket['id'], 'valid']);
        if ($update->rowCount() !== 1) {
            $pdo->rollBack();
            return ['ok' => false, 'status' => 409, 'reason' => 'already_checked_in'];
        }
        $pdo->prepare('INSERT INTO checkins (ticket_id,checked_in_by,result,created_at) VALUES (?,?,?,?)')
            ->execute([(int) $ticket['id'], (int) $actor['id'], 'accepted', $now]);
        $pdo->commit();
        return ['ok' => true, 'status' => 200, 'ticket_id' => (int) $ticket['id'], 'checkin_count' => 1];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'status' => 500, 'reason' => 'checkin_failed'];
    }
}

function ep_report(PDO $pdo, int $eventId): array
{
    $tickets = (int) $pdo->query('SELECT COUNT(*) FROM tickets WHERE event_id = ' . $eventId)->fetchColumn();
    $checked = (int) $pdo->query('SELECT COUNT(*) FROM tickets WHERE event_id = ' . $eventId . " AND status = 'checked_in'")->fetchColumn();
    $reviews = (int) $pdo->query('SELECT COUNT(*) FROM reviews WHERE event_id = ' . $eventId)->fetchColumn();
    $checkins = (int) $pdo->query('SELECT COUNT(*) FROM checkins c JOIN tickets t ON t.id = c.ticket_id WHERE t.event_id = ' . $eventId)->fetchColumn();
    return ['tickets' => $tickets, 'checked_in' => $checked, 'reviews' => $reviews, 'checkin_records' => $checkins];
}

function ep_layout(string $title, string $body, ?array $actor): void
{
    header('Content-Type: text/html; charset=utf-8');
    $nav = $actor
        ? '<span class="who">' . lab_e($actor['name']) . ' (' . lab_e($actor['role']) . ')</span>'
          . '<form method="post" action="/logout" class="inline"><button type="submit">Keluar</button></form>'
        : '<a href="/login">Masuk</a>';
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . lab_e($title) . ' - EventPass</title>'
        . '<link rel="stylesheet" href="/assets/style.css"></head><body>'
        . '<header class="top"><a class="brand" href="/events">EventPass</a>'
        . '<nav><a href="/events">Acara</a><a href="/tickets">Tiket</a>' . $nav . '</nav></header>'
        . '<main>' . $body . '</main>'
        . '<footer>Lab sintetis SIB 2026 · build ' . lab_e((string) (lab_build()['build_id'] ?? 'dev'))
        . ' · <span class="variant">' . lab_e(lab_variant()) . '</span></footer>'
        . '</body></html>';
}
