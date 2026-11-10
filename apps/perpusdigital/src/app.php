<?php
declare(strict_types=1);

/** PerpusDigital domain logic. */

function pd_money(int $cents): string
{
    return 'Rp ' . number_format($cents / 100, 0, ',', '.');
}

/**
 * Catalog search. The vulnerable build interpolates the term into SQL; the
 * patched build uses a prepared statement and keeps the visibility predicate.
 */
function pd_search(PDO $pdo, string $term, string $variant): array
{
    if ($variant === 'vulnerable') {
        $sql = "SELECT id, sku, title, author, shelf, copies, metadata_note, visibility FROM books WHERE (title LIKE '%$term%' OR author LIKE '%$term%') AND visibility = 'public' ORDER BY title";
        return $pdo->query($sql)->fetchAll();
    }
    $stmt = $pdo->prepare(
        'SELECT id, sku, title, author, shelf, copies, metadata_note, visibility
         FROM books
         WHERE (title LIKE :term OR author LIKE :term)
         AND visibility = :visibility
         ORDER BY title'
    );
    $stmt->execute(['term' => '%' . $term . '%', 'visibility' => 'public']);
    return $stmt->fetchAll();
}

function pd_book(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function pd_loans_for(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT l.*, b.title, b.sku
         FROM loans l JOIN books b ON b.id = l.book_id
         WHERE l.user_id = ? ORDER BY l.borrowed_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function pd_all_loans(PDO $pdo): array
{
    return $pdo->query(
        'SELECT l.*, b.title, b.sku, u.email AS member_email
         FROM loans l JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id
         ORDER BY l.borrowed_at DESC'
    )->fetchAll();
}

function pd_fines_for(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT f.*, l.status AS loan_status, b.title
         FROM fines f JOIN loans l ON l.id = f.loan_id JOIN books b ON b.id = l.book_id
         WHERE f.user_id = ? ORDER BY f.id'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function pd_borrow(PDO $pdo, array $actor, int $bookId): array
{
    $book = pd_book($pdo, $bookId);
    if (!$book || $book['visibility'] !== 'public') {
        return ['ok' => false, 'status' => 404, 'reason' => 'book_not_found'];
    }
    if ((int) $book['copies'] < 1) {
        return ['ok' => false, 'status' => 409, 'reason' => 'no_copy_available'];
    }
    $id = (int) $pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM loans')->fetchColumn();
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO loans (id,user_id,book_id,borrowed_at,due_at,returned_at,status) VALUES (?,?,?,?,?,?,?)')
            ->execute([$id, (int) $actor['id'], $bookId, $now->format('Y-m-d\TH:i:s\Z'), $now->modify('+14 days')->format('Y-m-d\TH:i:s\Z'), null, 'active']);
        $pdo->prepare('UPDATE books SET copies = copies - 1 WHERE id = ? AND copies > 0')->execute([$bookId]);
        $pdo->commit();
        return ['ok' => true, 'status' => 201, 'loan_id' => $id];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'status' => 500, 'reason' => 'borrow_failed'];
    }
}

function pd_return(PDO $pdo, array $actor, int $loanId): array
{
    $stmt = $pdo->prepare('SELECT * FROM loans WHERE id = ?');
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch();
    if (!$loan) {
        return ['ok' => false, 'status' => 404, 'reason' => 'loan_not_found'];
    }
    if ((int) $loan['user_id'] !== (int) $actor['id'] && ($actor['role'] ?? '') !== 'staff') {
        return ['ok' => false, 'status' => 403, 'reason' => 'not_loan_owner'];
    }
    if ($loan['status'] === 'returned') {
        return ['ok' => false, 'status' => 409, 'reason' => 'already_returned'];
    }
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE loans SET returned_at = ?, status = ? WHERE id = ? AND status = ?')
            ->execute([$now->format('Y-m-d\TH:i:s\Z'), 'returned', $loanId, 'active']);
        $pdo->prepare('UPDATE books SET copies = copies + 1 WHERE id = ?')->execute([(int) $loan['book_id']]);
        $pdo->commit();
        return ['ok' => true, 'status' => 200, 'loan_id' => $loanId];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'status' => 500, 'reason' => 'return_failed'];
    }
}

/**
 * Fine waiver. The vulnerable build only hides the staff control in the UI and
 * resolves the fine regardless of the loan state. The patched build enforces
 * staff role and the returned-loan prerequisite in one conditional update.
 */
function pd_waive_fine(PDO $pdo, array $actor, int $fineId, string $variant): array
{
    if ($variant !== 'vulnerable' && ($actor['role'] ?? '') !== 'staff') {
        return ['ok' => false, 'status' => 403, 'reason' => 'staff_role_required'];
    }
    $stmt = $pdo->prepare(
        'SELECT f.*, l.status AS loan_status FROM fines f JOIN loans l ON l.id = f.loan_id WHERE f.id = ?'
    );
    $stmt->execute([$fineId]);
    $fine = $stmt->fetch();
    if (!$fine) {
        return ['ok' => false, 'status' => 404, 'reason' => 'fine_not_found'];
    }
    if ($fine['status'] === 'resolved') {
        return ['ok' => false, 'status' => 409, 'reason' => 'fine_already_resolved'];
    }
    if ($variant !== 'vulnerable' && $fine['loan_status'] !== 'returned') {
        return ['ok' => false, 'status' => 409, 'reason' => 'loan_not_returned'];
    }
    if ($variant === 'vulnerable') {
        $pdo->prepare('UPDATE fines SET status = ? WHERE id = ?')->execute(['resolved', $fineId]);
    } else {
        $update = $pdo->prepare('UPDATE fines SET status = ? WHERE id = ? AND status = ?');
        $update->execute(['resolved', $fineId, 'outstanding']);
        if ($update->rowCount() !== 1) {
            return ['ok' => false, 'status' => 409, 'reason' => 'fine_state_changed'];
        }
    }
    return ['ok' => true, 'status' => 200, 'fine_id' => $fineId];
}

function pd_update_book(PDO $pdo, array $actor, int $bookId, array $fields, string $variant): array
{
    if ($variant !== 'vulnerable' && ($actor['role'] ?? '') !== 'staff') {
        return ['ok' => false, 'status' => 403, 'reason' => 'staff_role_required'];
    }
    $book = pd_book($pdo, $bookId);
    if (!$book) {
        return ['ok' => false, 'status' => 404, 'reason' => 'book_not_found'];
    }
    $title = isset($fields['title']) ? trim((string) $fields['title']) : $book['title'];
    $author = isset($fields['author']) ? trim((string) $fields['author']) : $book['author'];
    $shelf = isset($fields['shelf']) ? trim((string) $fields['shelf']) : $book['shelf'];
    if ($title === '' || $author === '') {
        return ['ok' => false, 'status' => 400, 'reason' => 'invalid_metadata'];
    }
    $pdo->prepare('UPDATE books SET title = ?, author = ?, shelf = ? WHERE id = ?')
        ->execute([$title, $author, $shelf, $bookId]);
    return ['ok' => true, 'status' => 200, 'book_id' => $bookId];
}

function pd_layout(string $title, string $body, ?array $actor): void
{
    header('Content-Type: text/html; charset=utf-8');
    $nav = $actor
        ? '<span class="who">' . lab_e($actor['name']) . ' (' . lab_e($actor['role']) . ')</span>'
          . '<form method="post" action="/logout" class="inline"><button type="submit">Keluar</button></form>'
        : '<a href="/login">Masuk</a>';
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . lab_e($title) . ' - PerpusDigital</title>'
        . '<link rel="stylesheet" href="/assets/style.css"></head><body>'
        . '<header class="top"><a class="brand" href="/catalog">PerpusDigital</a>'
        . '<nav><a href="/catalog">Katalog</a><a href="/loans">Pinjaman</a>'
        . '<a href="/fines">Denda</a>' . $nav . '</nav></header>'
        . '<main>' . $body . '</main>'
        . '<footer>Lab sintetis SIB 2026 · build ' . lab_e((string) (lab_build()['build_id'] ?? 'dev'))
        . ' · <span class="variant">' . lab_e(lab_variant()) . '</span></footer>'
        . '</body></html>';
}
