<?php
declare(strict_types=1);

/**
 * EventPass end-to-end checks: normal flows, three bounded conditions, and the
 * patched retest. The DOM indicator is inert and has no network callback.
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
        $failures[] = $name . ($detail !== '' ? " - $detail" : '');
        echo "FAIL  $name" . ($detail !== '' ? " - $detail" : '') . "\n";
    }
}

function http(string $method, string $path, $body = null, ?string $jar = null): array
{
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    ]);
    if ($jar !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $decoded = json_decode((string) $raw, true);
    return ['status' => $status, 'body' => (string) $raw, 'json' => is_array($decoded) ? $decoded : null];
}

function jar(string $name): string
{
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ep-' . $name . '-' . getmypid() . '.cookies';
    if (is_file($path)) {
        unlink($path);
    }
    return $path;
}

$health = http('GET', '/healthz');
check('health endpoint responds', $health['status'] === 200);
check('health reports expected variant', ($health['json']['variant'] ?? null) === $variant);

// --- Normal attendee flow -------------------------------------------------
$jarL = jar('laras');
$login = http('POST', '/login', ['email' => 'laras@eventpass.test', 'password' => 'LabPass!2026'], $jarL);
check('attendee can sign in', $login['status'] === 200, 'status ' . $login['status']);
check('event list is available', count(http('GET', '/events', null, $jarL)['json']['events'] ?? []) >= 3);

$create = http('POST', '/tickets/create', ['event_id' => 1], $jarL);
check('attendee can create a ticket', $create['status'] === 201, 'status ' . $create['status']);
$ticketId = (int) ($create['json']['ticket_id'] ?? 0);
$ticketCode = (string) ($create['json']['code'] ?? '');
check('ticket returns a code', $ticketId > 0 && $ticketCode !== '');

$rename = http('POST', '/tickets/update', ['ticket_id' => $ticketId, 'display_name' => 'Laras A.'], $jarL);
check('attendee can update an allowed field', $rename['status'] === 200, 'status ' . $rename['status']);

// --- Bounded condition 2: organizer-controlled ticket property -----------
$tierUpdate = http('POST', '/tickets/update', ['ticket_id' => $ticketId, 'tier' => 'vip', 'price_cents' => 1], $jarL);
if ($variant === 'vulnerable') {
    check('VULN attendee changes an organizer-controlled tier', $tierUpdate['status'] === 200, 'status ' . $tierUpdate['status']);
    $tickets = http('GET', '/tickets', null, $jarL);
    $tier = '';
    foreach ($tickets['json']['tickets'] ?? [] as $ticket) {
        if ((int) $ticket['id'] === $ticketId) {
            $tier = $ticket['tier'];
        }
    }
    check('VULN tier change is persisted', $tier === 'vip', 'tier ' . $tier);
} else {
    check('PATCHED forbidden ticket field is rejected', $tierUpdate['status'] === 403, 'status ' . $tierUpdate['status']);
    $tickets = http('GET', '/tickets', null, $jarL);
    $tier = '';
    foreach ($tickets['json']['tickets'] ?? [] as $ticket) {
        if ((int) $ticket['id'] === $ticketId) {
            $tier = $ticket['tier'];
        }
    }
    check('PATCHED tier is unchanged', $tier === 'standard', 'tier ' . $tier);
}

// --- Reviews --------------------------------------------------------------
$plain = http('POST', '/reviews', ['event_id' => 1, 'body' => 'Acara yang menarik dan rapi.'], $jarL);
check('attendee can post a plain review', $plain['status'] === 201, 'status ' . $plain['status']);

$xssPayload = '<img src=x onerror="document.body.dataset.labProof=\'EP-DOM-9127\'">';
$stored = http('POST', '/reviews', ['event_id' => 1, 'body' => $xssPayload], $jarL);
check('attendee can save the designated review payload', $stored['status'] === 201, 'status ' . $stored['status']);

// --- Bounded condition 1: stored review rendered in a second account -----
$jarD = jar('dimas');
$loginD = http('POST', '/login', ['email' => 'dimas@eventpass.test', 'password' => 'LabPass!2026'], $jarD);
check('second attendee can sign in', $loginD['status'] === 200);

$page = http('GET', '/event?id=1&format=html', null, $jarD);
check('event page renders for the second attendee', $page['status'] === 200 && str_contains($page['body'], 'Ulasan'));
if ($variant === 'vulnerable') {
    check('VULN stored review renders an executable local DOM indicator', str_contains($page['body'], '<img src=x onerror='), 'raw img tag not found');
} else {
    check('PATCHED stored review is encoded', str_contains($page['body'], '&lt;img src=x') && !str_contains($page['body'], '<img src=x onerror='));
}

// --- Bounded condition 3: duplicate check-in -----------------------------
$jarO = jar('panitia');
$loginO = http('POST', '/login', ['email' => 'panitia@eventpass.test', 'password' => 'StaffPass!2026'], $jarO);
check('organizer can sign in', $loginO['status'] === 200);

$first = http('POST', '/checkin', ['ticket_code' => $ticketCode], $jarO);
check('first check-in succeeds', $first['status'] === 200, 'status ' . $first['status']);

$second = http('POST', '/checkin', ['ticket_code' => $ticketCode], $jarO);
if ($variant === 'vulnerable') {
    check('VULN duplicate check-in is accepted', $second['status'] === 200 && (int) ($second['json']['checkin_count'] ?? 0) >= 2, 'status ' . $second['status']);
} else {
    check('PATCHED duplicate check-in is rejected', $second['status'] === 409, 'status ' . $second['status']);
}

$attendeeCheckin = http('POST', '/checkin', ['ticket_code' => $ticketCode], $jarL);
check('attendee cannot operate check-in', $attendeeCheckin['status'] === 403, 'status ' . $attendeeCheckin['status']);

// --- Organizer report and evidence ---------------------------------------
$report = http('GET', '/organizer/event?id=1', null, $jarO);
check('organizer can read an event report', $report['status'] === 200);
$records = (int) ($report['json']['report']['checkin_records'] ?? 0);
check($variant === 'vulnerable' ? 'VULN report records two check-ins' : 'PATCHED report records one check-in', $records === ($variant === 'vulnerable' ? 2 : 1), 'records ' . $records);

$evidence = http('GET', '/organizer/evidence?limit=200', null, $jarO);
check('organizer can read the evidence log', $evidence['status'] === 200);
$actions = array_column($evidence['json']['events'] ?? [], 'action');
check('evidence records reviews', in_array('review.create', $actions, true));
check('evidence records ticket updates', in_array('ticket.update', $actions, true));
check('evidence records check-ins', in_array('checkin.record', $actions, true));
$leaked = false;
foreach ($evidence['json']['events'] ?? [] as $event) {
    if (stripos((string) $event['detail'], 'LabPass') !== false || stripos((string) $event['detail'], 'StaffPass') !== false) {
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
