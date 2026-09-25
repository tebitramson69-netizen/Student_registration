<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Allocate the next receipt number for an institution.
 * Must run inside a transaction: the row lock is what stops two cashiers
 * issuing the same receipt number at the same moment.
 */
function next_receipt_no(PDO $pdo, int $institutionId): string
{
    $stmt = $pdo->prepare(
        'SELECT receipt_prefix, next_receipt_no FROM institutions WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$institutionId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('Unknown institution.');
    }

    $pdo->prepare('UPDATE institutions SET next_receipt_no = next_receipt_no + 1 WHERE id = ?')
        ->execute([$institutionId]);

    return sprintf('%s-%06d', $row['receipt_prefix'], (int)$row['next_receipt_no']);
}

/**
 * Record a payment against an enrolment and issue its receipt, atomically.
 * Returns the new payment id.
 */
function record_payment(int $institutionId, int $enrolmentId, int $amountFcfa, string $method, string $reference, string $paidOn, string $note, int $userId): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Re-check the enrolment belongs to this institution inside the
        // transaction, so a forged enrolment_id cannot cross tenants.
        $check = $pdo->prepare('SELECT id FROM enrolments WHERE id = ? AND institution_id = ?');
        $check->execute([$enrolmentId, $institutionId]);
        if (!$check->fetch()) {
            throw new RuntimeException('Enrolment not found.');
        }

        $receiptNo   = next_receipt_no($pdo, $institutionId);
        $receiptCode = strtoupper(bin2hex(random_bytes(4)));

        $insert = $pdo->prepare(
            'INSERT INTO payments
                (institution_id, enrolment_id, entry_type, amount_fcfa, method,
                 reference, paid_on, note, receipt_no, receipt_code, recorded_by)
             VALUES (?, ?, \'payment\', ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $institutionId, $enrolmentId, $amountFcfa, $method,
            $reference, $paidOn, $note, $receiptNo, $receiptCode, $userId,
        ]);

        $id = (int)$pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Reverse a payment by writing a compensating entry.
 * The original row is never modified — that is what makes the ledger
 * defensible when a student disputes what they paid.
 */
function reverse_payment(int $institutionId, int $paymentId, string $reason, int $userId): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT id, enrolment_id, amount_fcfa, method
               FROM payments
              WHERE id = ? AND institution_id = ? AND entry_type = \'payment\'
                FOR UPDATE'
        );
        $stmt->execute([$paymentId, $institutionId]);
        $original = $stmt->fetch();
        if (!$original) {
            throw new RuntimeException('Payment not found.');
        }

        $already = $pdo->prepare('SELECT id FROM payments WHERE reverses_payment_id = ?');
        $already->execute([$paymentId]);
        if ($already->fetch()) {
            throw new RuntimeException('This payment has already been reversed.');
        }

        $receiptNo   = next_receipt_no($pdo, $institutionId);
        $receiptCode = strtoupper(bin2hex(random_bytes(4)));

        $insert = $pdo->prepare(
            'INSERT INTO payments
                (institution_id, enrolment_id, entry_type, reverses_payment_id,
                 amount_fcfa, method, paid_on, note, receipt_no, receipt_code, recorded_by)
             VALUES (?, ?, \'reversal\', ?, ?, ?, CURDATE(), ?, ?, ?, ?)'
        );
        $insert->execute([
            $institutionId, (int)$original['enrolment_id'], $paymentId,
            (int)$original['amount_fcfa'], $original['method'],
            $reason, $receiptNo, $receiptCode, $userId,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * SQL fragment for the net amount paid on an enrolment.
 * Reversals subtract, so the balance is always derived, never stored.
 */
const PAID_SUM_SQL = "COALESCE(SUM(CASE WHEN p.entry_type = 'payment' THEN p.amount_fcfa ELSE -p.amount_fcfa END), 0)";
