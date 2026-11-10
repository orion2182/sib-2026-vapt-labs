<?php
declare(strict_types=1);

/**
 * PerpusDigital end-to-end checks: normal library flows, three bounded
 * conditions, and the patched retest.
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
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pd-' . $name . '-' . getmypid() . '.cookies';
    if (is_file($path)) {
        unlink($path);
    }
    return $path;
}

$health = http('GET', '/healthz');
check('health endpoint responds', $health['status'] === 200);
check('health reports expected variant', ($health['json']['variant'] ?? null) === $variant);

// --- Normal member flow ---------------------------------------------------
$jarN = jar('nabila');
$login = http('POST', '/login', ['email' => 'nabila@perpus.test', 'password' => 'LabPass!2026'], $jarN);
check('member can sign in', $login['status'] === 200, 'status ' . $login['status']);

$search = http('GET', '/catalog?q=' . rawurlencode('Keamanan'), null, $jarN);
check('normal search returns public results', $search['status'] === 200 && count($search['json']['books'] ?? []) >= 1);
$publicOnly = true;
foreach ($search['json']['books'] ?? [] as $book) {
    if (($book['visibility'] ?? 'public') !== 'public') {
        $publicOnly = false;
    }
}
check('normal search hides private rows', $publicOnly);

$borrow = http('POST', '/borrow', ['book_id' => 105], $jarN);
check('member can borrow a book', $borrow['status'] === 201, 'status ' . $borrow['status']);
$loanId = (int) ($borrow['json']['loan_id'] ?? 0);
check('borrow returns a loan id', $loanId > 0);

$return = http('POST', '/return', ['loan_id' => $loanId], $jarN);
check('member can return the book', $return['status'] === 200, 'status ' . $return['status']);

$fines = http('GET', '/fines', null, $jarN);
check('member sees own fines', $fines['status'] === 200 && count($fines['json']['fines'] ?? []) >= 1);

// --- Bounded condition 1: search query injection -------------------------
$injection = http('GET', '/catalog?q=' . rawurlencode("' OR 1=1) --"), null, $jarN);
$privateRows = 0;
$markerSeen = false;
foreach ($injection['json']['books'] ?? [] as $book) {
    if (($book['visibility'] ?? '') === 'private') {
        $privateRows++;
        if (str_contains((string) ($book['metadata_note'] ?? ''), 'PD-MARKER-8K3L')) {
            $markerSeen = true;
        }
    }
}
if ($variant === 'vulnerable') {
    check('VULN search exposes the private marker record', $injection['status'] === 200 && $privateRows === 1 && $markerSeen, 'private rows ' . $privateRows);
} else {
    check('PATCHED search keeps the private record hidden', $injection['status'] === 200 && $privateRows === 0, 'private rows ' . $privateRows);
}

// --- Bounded condition 2: member invokes staff metadata function ---------
$memberUpdate = http('POST', '/staff/book/update', ['book_id' => 112, 'title' => 'Judul Diubah Member'], $jarN);
if ($variant === 'vulnerable') {
    check('VULN member changes book metadata', $memberUpdate['status'] === 200, 'status ' . $memberUpdate['status']);
    $after = http('GET', '/book?id=112', null, $jarN);
    check('VULN metadata change persisted', ($after['json']['book']['title'] ?? '') === 'Judul Diubah Member');
} else {
    check('PATCHED member metadata change is rejected', $memberUpdate['status'] === 403, 'status ' . $memberUpdate['status']);
    $after = http('GET', '/book?id=112', null, $jarN);
    check('PATCHED metadata is unchanged', ($after['json']['book']['title'] ?? '') === 'Audit Sistem Informasi');
}

// --- Staff session (the normal fine workflow is staff-only) --------------
$jarS = jar('dewi');
$staffLogin = http('POST', '/login', ['email' => 'dewi@perpus.test', 'password' => 'StaffPass!2026'], $jarS);
check('staff can sign in', $staffLogin['status'] === 200, 'status ' . $staffLogin['status']);

// --- Bounded condition 3: premature fine waiver --------------------------
// Use the authorized staff actor so the test isolates the loan-state rule
// rather than the separate function-level authorization control.
$waive = http('POST', '/fines/waive', ['fine_id' => 7001], $jarS);
if ($variant === 'vulnerable') {
    check('VULN fine waived before the book is returned', $waive['status'] === 200, 'status ' . $waive['status']);
    $afterFine = http('GET', '/fines', null, $jarN);
    $status = '';
    foreach ($afterFine['json']['fines'] ?? [] as $fine) {
        if ((int) $fine['id'] === 7001) {
            $status = $fine['status'];
        }
    }
    check('VULN premature waiver is persisted', $status === 'resolved', 'status ' . $status);
} else {
    check('PATCHED premature waiver is rejected', $waive['status'] === 409, 'status ' . $waive['status']);
    $memberWaive = http('POST', '/fines/waive', ['fine_id' => 7001], $jarN);
    check('PATCHED member fine action is forbidden', $memberWaive['status'] === 403, 'status ' . $memberWaive['status']);
    $afterFine = http('GET', '/fines', null, $jarN);
    $status = '';
    foreach ($afterFine['json']['fines'] ?? [] as $fine) {
        if ((int) $fine['id'] === 7001) {
            $status = $fine['status'];
        }
    }
    check('PATCHED fine remains outstanding', $status === 'outstanding', 'status ' . $status);
}

// --- Staff workflows ------------------------------------------------------
check('staff sees the metadata UI', (http('GET', '/staff/books', null, $jarS)['status']) === 200);
check('member cannot open the metadata UI', (http('GET', '/staff/books', null, $jarN)['status']) === 403);
$unknownField = http('POST', '/staff/book/update', ['book_id' => 101, 'field' => 'title', 'value' => 'x'], $jarS);
check('unknown metadata fields are rejected', $unknownField['status'] === 400, 'status ' . $unknownField['status']);
check('staff sees all loans', (http('GET', '/staff/loans', null, $jarS)['status']) === 200);
check('member cannot use staff loan listing', (http('GET', '/staff/loans', null, $jarN)['status']) === 403);

$staffUpdate = http('POST', '/staff/book/update', ['book_id' => 111, 'title' => 'Kontinuitas Bisnis Edisi 2'], $jarS);
check('staff can update book metadata', $staffUpdate['status'] === 200, 'status ' . $staffUpdate['status']);

$eligible = http('POST', '/fines/waive', ['fine_id' => 7002], $jarS);
check('staff can waive an eligible returned-loan fine', $eligible['status'] === 200, 'status ' . $eligible['status']);

// --- Evidence ------------------------------------------------------------
$evidence = http('GET', '/staff/evidence?limit=200', null, $jarS);
check('staff can read the evidence log', $evidence['status'] === 200);
$actions = array_column($evidence['json']['events'] ?? [], 'action');
check('evidence records searches', in_array('catalog.search', $actions, true));
check('evidence records fine decisions', in_array('fine.waive', $actions, true));
check('evidence records staff metadata decisions', in_array('staff.book.update', $actions, true));
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
