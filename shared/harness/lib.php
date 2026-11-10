<?php
declare(strict_types=1);

/**
 * Shared VAPT lab harness.
 *
 * Deliberately small: state paths, SQLite access, deterministic reset,
 * evidence logging, sessions, and JSON helpers. Domain logic belongs to each
 * target app, never here.
 */

const LAB_HARNESS_VERSION = '1.0.0';

/** Repository root in a checkout, or the package root in a built distribution. */
function lab_root(): string
{
    return dirname(__DIR__, 2);
}

/** Mutable runtime state (database, sessions, logs). Never committed. */
function lab_state_dir(): string
{
    $dir = lab_root() . DIRECTORY_SEPARATOR . 'run';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

/** Build identity written by tools/lab.ps1 build. */
function lab_build(): array
{
    static $build = null;
    if ($build === null) {
        $file = lab_root() . DIRECTORY_SEPARATOR . 'build.json';
        $build = is_file($file)
            ? (json_decode((string) file_get_contents($file), true) ?: [])
            : ['target' => 'dev', 'variant' => 'vulnerable', 'build_id' => 'dev'];
    }
    return $build;
}

function lab_variant(): string
{
    return (string) (lab_build()['variant'] ?? 'vulnerable');
}

function lab_target(): string
{
    return (string) (lab_build()['target'] ?? 'dev');
}

function lab_db_path(): string
{
    return getenv('LAB_DB') ?: lab_state_dir() . DIRECTORY_SEPARATOR . 'lab.sqlite';
}

/** Shared PDO handle. The schema is created by seed/fixture.php. */
function lab_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $path = lab_db_path();
    $fresh = !is_file($path);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    if ($fresh) {
        lab_seed($pdo);
    }
    return $pdo;
}

/** Load the target fixture. Idempotent only on a fresh database. */
function lab_seed(PDO $pdo): void
{
    $fixture = lab_root() . DIRECTORY_SEPARATOR . 'seed' . DIRECTORY_SEPARATOR . 'fixture.php';
    if (!is_file($fixture)) {
        throw new RuntimeException('Missing fixture: ' . $fixture);
    }
    $seed = require $fixture;
    if (!is_callable($seed)) {
        throw new RuntimeException('Fixture must return a callable');
    }
    $seed($pdo);
}

/** Destroy runtime state and reseed. This is the documented reset path. */
function lab_reset(): void
{
    $path = lab_db_path();
    foreach ([$path, $path . '-wal', $path . '-shm'] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    $evidence = lab_state_dir() . DIRECTORY_SEPARATOR . 'evidence.ndjson';
    if (is_file($evidence)) {
        unlink($evidence);
    }
    lab_db(); // recreates and seeds
}

function lab_request_id(): string
{
    static $id = null;
    if ($id === null) {
        $id = 'REQ-' . date('Ymd') . '-' . bin2hex(random_bytes(5));
    }
    return $id;
}

function lab_now(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
}

/**
 * Append one evidence event. Secrets, passwords, and tokens must never be
 * passed in $detail.
 */
function lab_evidence(
    PDO $pdo,
    ?array $actor,
    string $action,
    string $objectType,
    string $objectId,
    string $result,
    array $detail = []
): string {
    $id = sprintf('EV-%s-%s', date('Ymd'), strtoupper(bin2hex(random_bytes(4))));
    $row = [
        'id' => $id,
        'request_id' => lab_request_id(),
        'ts_utc' => lab_now(),
        'actor' => $actor['email'] ?? 'anonymous',
        'role' => $actor['role'] ?? 'anonymous',
        'action' => $action,
        'object_type' => $objectType,
        'object_id' => $objectId,
        'result' => $result,
        'detail' => json_encode($detail, JSON_UNESCAPED_SLASHES),
    ];
    $stmt = $pdo->prepare(
        'INSERT INTO evidence_events (id, request_id, ts_utc, actor, role, action, object_type, object_id, result, detail)
         VALUES (:id, :request_id, :ts_utc, :actor, :role, :action, :object_type, :object_id, :result, :detail)'
    );
    $stmt->execute($row);
    $line = json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    file_put_contents(lab_state_dir() . DIRECTORY_SEPARATOR . 'evidence.ndjson', $line, FILE_APPEND | LOCK_EX);
    return $id;
}

function lab_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $dir = lab_state_dir() . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    session_save_path($dir);
    session_name('LABSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/** Current authenticated actor, or null. */
function lab_actor(PDO $pdo): ?array
{
    lab_session();
    $uid = $_SESSION['uid'] ?? null;
    if ($uid === null) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, email, name, role FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function lab_login(PDO $pdo, string $email, string $password): ?array
{
    $stmt = $pdo->prepare('SELECT id, email, name, role, password_hash FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        lab_evidence($pdo, null, 'auth.login', 'user', substr($email, 0, 64), 'denied');
        return null;
    }
    unset($user['password_hash']);
    lab_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    lab_evidence($pdo, $user, 'auth.login', 'user', (string) $user['id'], 'allowed');
    return $user;
}

function lab_logout(PDO $pdo): void
{
    $actor = lab_actor($pdo);
    lab_session();
    $_SESSION = [];
    session_destroy();
    if ($actor) {
        lab_evidence($pdo, $actor, 'auth.logout', 'user', (string) $actor['id'], 'allowed');
    }
}

function lab_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Request-Id: ' . lab_request_id());
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function lab_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return $_POST;
    }
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($type, 'application/json') !== false) {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
    parse_str($raw, $parsed);
    return $parsed;
}

function lab_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function lab_require_method(string $method): void
{
    if (lab_method() !== $method) {
        header('Allow: ' . $method);
        lab_json(['error' => 'method_not_allowed', 'expected' => $method], 405);
    }
}

function lab_require_actor(PDO $pdo): array
{
    $actor = lab_actor($pdo);
    if (!$actor) {
        lab_json(['error' => 'authentication_required'], 401);
    }
    return $actor;
}

function lab_require_role(array $actor, string $role): void
{
    if (($actor['role'] ?? '') !== $role) {
        lab_json(['error' => 'forbidden', 'required_role' => $role], 403);
    }
}

function lab_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
