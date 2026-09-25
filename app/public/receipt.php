<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$institutionId = current_institution_id();

$id = int_param($_GET, 'id');
if ($id === null) {
    redirect('index.php');
}

$stmt = db()->prepare(
    'SELECT p.*, s.id AS student_id, s.first_name, s.last_name, s.student_code, s.phone,
            pr.name AS programme, e.intake_label, e.agreed_fee_fcfa,
            i.name AS centre_name, i.phone AS centre_phone, i.address AS centre_address,
            u.full_name AS cashier,
            (SELECT COUNT(*) FROM payments r WHERE r.reverses_payment_id = p.id) AS is_reversed
       FROM payments p
       JOIN enrolments   e  ON e.id  = p.enrolment_id
       JOIN students     s  ON s.id  = e.student_id
       JOIN programmes   pr ON pr.id = e.programme_id
       JOIN institutions i  ON i.id  = p.institution_id
       LEFT JOIN users   u  ON u.id  = p.recorded_by
      WHERE p.id = ? AND p.institution_id = ?'
);
$stmt->execute([$id, $institutionId]);
$r = $stmt->fetch();
if (!$r) {
    http_response_code(404);
    exit('Receipt not found.');
}

// Running balance on this enrolment as at now.
$paidStmt = db()->prepare('SELECT ' . PAID_SUM_SQL . ' FROM payments p WHERE p.enrolment_id = ?');
$paidStmt->execute([(int)$r['enrolment_id']]);
$paid    = (int)$paidStmt->fetchColumn();
$balance = (int)$r['agreed_fee_fcfa'] - $paid;

$verifyUrl = rtrim(config()['base_url'], '/') . '/verify.php?code=' . $r['receipt_code'];

$smsText = sprintf(
    '%s: receipt %s. %s received from %s for %s on %s. Balance %s. Verify: %s',
    $r['centre_name'], $r['receipt_no'], money((int)$r['amount_fcfa']),
    $r['first_name'] . ' ' . $r['last_name'], $r['programme'], $r['paid_on'],
    money($balance), $verifyUrl
);

page_header('Receipt ' . $r['receipt_no']);
?>
<div class="receipt">
    <h1><?= $r['entry_type'] === 'reversal' ? 'REVERSAL' : 'RECEIPT' ?></h1>
    <div class="centre">
        <strong><?= e($r['centre_name']) ?></strong><br>
        <?= e($r['centre_address']) ?><?= $r['centre_phone'] !== '' ? ' · ' . e($r['centre_phone']) : '' ?>
    </div>

    <?php if ((int)$r['is_reversed'] > 0): ?>
        <p class="error">This receipt has been reversed and is no longer valid.</p>
    <?php endif; ?>

    <dl>
        <dt>Receipt no.</dt><dd><?= e($r['receipt_no']) ?></dd>
        <dt>Date</dt><dd><?= e($r['paid_on']) ?></dd>
        <dt>Student</dt><dd><?= e($r['first_name'] . ' ' . $r['last_name']) ?></dd>
        <dt>Code</dt><dd><?= e($r['student_code']) ?></dd>
        <dt>Programme</dt><dd><?= e($r['programme']) ?></dd>
        <dt>Method</dt><dd><?= e($r['method']) ?></dd>
        <?php if ($r['reference'] !== ''): ?>
            <dt>Reference</dt><dd><?= e($r['reference']) ?></dd>
        <?php endif; ?>
        <?php if ($r['note'] !== ''): ?>
            <dt>Note</dt><dd><?= e($r['note']) ?></dd>
        <?php endif; ?>
        <dt>Amount</dt>
        <dd class="total"><?= $r['entry_type'] === 'reversal' ? '-' : '' ?><?= e(money((int)$r['amount_fcfa'])) ?></dd>
        <dt>Balance after</dt><dd><?= e(money($balance)) ?></dd>
        <?php if ($r['cashier'] !== null): ?>
            <dt>Received by</dt><dd><?= e($r['cashier']) ?></dd>
        <?php endif; ?>
    </dl>

    <div class="verify">
        Verification code <strong><?= e($r['receipt_code']) ?></strong><br>
        <?= e($verifyUrl) ?>
    </div>
</div>

<div class="no-print" style="max-width:480px;margin:0 auto;">
    <button class="btn" onclick="window.print()">Print</button>
    <a class="btn secondary" href="student_view.php?id=<?= (int)$r['student_id'] ?>">Back to student</a>

    <h2>Send by SMS or WhatsApp</h2>
    <p class="muted">Copy this and send it to <?= e($r['phone']) ?>.</p>
    <textarea rows="5" readonly onclick="this.select()"><?= e($smsText) ?></textarea>
</div>
<?php page_footer(); ?>
