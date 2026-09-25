<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
$institutionId = $user['institution_id'];

$id = int_param($_GET, 'id');
if ($id === null) {
    redirect('students.php');
}

$stmt = db()->prepare('SELECT * FROM students WHERE id = ? AND institution_id = ?');
$stmt->execute([$id, $institutionId]);
$student = $stmt->fetch();
if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

// Balance is always derived from the ledger, never stored.
$enrolStmt = db()->prepare(
    'SELECT e.id, e.intake_label, e.agreed_fee_fcfa, e.status, e.started_on,
            pr.name AS programme,
            ' . PAID_SUM_SQL . ' AS paid
       FROM enrolments e
       JOIN programmes pr ON pr.id = e.programme_id
       LEFT JOIN payments p ON p.enrolment_id = e.id
      WHERE e.student_id = ? AND e.institution_id = ?
      GROUP BY e.id
      ORDER BY e.started_on DESC'
);
$enrolStmt->execute([$id, $institutionId]);
$enrolments = $enrolStmt->fetchAll();

$payStmt = db()->prepare(
    'SELECT p.*, pr.name AS programme,
            (SELECT COUNT(*) FROM payments r WHERE r.reverses_payment_id = p.id) AS is_reversed
       FROM payments p
       JOIN enrolments e  ON e.id = p.enrolment_id
       JOIN programmes pr ON pr.id = e.programme_id
      WHERE e.student_id = ? AND p.institution_id = ?
      ORDER BY p.id DESC'
);
$payStmt->execute([$id, $institutionId]);
$payments = $payStmt->fetchAll();

page_header($student['first_name'] . ' ' . $student['last_name']);
?>
<h1><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>
<p class="muted">
    <?= e($student['student_code']) ?> · <?= e($student['phone']) ?>
    <?= $student['email'] !== '' ? ' · ' . e($student['email']) : '' ?>
    · <a href="student_form.php?id=<?= (int)$id ?>">Edit</a>
</p>

<h2>Programmes</h2>
<?php if (!$enrolments): ?>
    <p class="muted">Not enrolled on any programme yet.</p>
<?php else: ?>
<div class="table-scroll">
<table>
    <tr>
        <th>Programme</th><th>Intake</th><th>Status</th>
        <th class="num">Fee</th><th class="num">Paid</th><th class="num">Balance</th><th></th>
    </tr>
    <?php foreach ($enrolments as $en):
        $balance = (int)$en['agreed_fee_fcfa'] - (int)$en['paid']; ?>
    <tr>
        <td><?= e($en['programme']) ?></td>
        <td><?= e($en['intake_label']) ?></td>
        <td><?= e($en['status']) ?></td>
        <td class="num"><?= e(money((int)$en['agreed_fee_fcfa'])) ?></td>
        <td class="num"><?= e(money((int)$en['paid'])) ?></td>
        <td class="num">
            <span class="pill <?= $balance > 0 ? 'owing' : 'clear' ?>">
                <?= e(money($balance)) ?>
            </span>
        </td>
        <td><a href="payment_new.php?enrolment_id=<?= (int)$en['id'] ?>">Record payment</a></td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>
<p><a class="btn secondary" href="enrol.php?student_id=<?= (int)$id ?>">Enrol on another programme</a></p>

<h2>Payment history</h2>
<?php if (!$payments): ?>
    <p class="muted">No payments recorded.</p>
<?php else: ?>
<div class="table-scroll">
<table>
    <tr><th>Receipt</th><th>Date</th><th>Programme</th><th>Method</th><th class="num">Amount</th><th></th></tr>
    <?php foreach ($payments as $p):
        $isReversal = $p['entry_type'] === 'reversal';
        $struck = $isReversal || (int)$p['is_reversed'] > 0; ?>
    <tr>
        <td><a href="receipt.php?id=<?= (int)$p['id'] ?>"><?= e($p['receipt_no']) ?></a></td>
        <td><?= e($p['paid_on']) ?></td>
        <td><?= e($p['programme']) ?></td>
        <td><?= e($p['method']) ?><?= $p['reference'] !== '' ? ' · ' . e($p['reference']) : '' ?></td>
        <td class="num<?= $struck ? ' reversed' : '' ?>">
            <?= $isReversal ? '-' : '' ?><?= e(money((int)$p['amount_fcfa'])) ?>
        </td>
        <td>
            <?php if (!$isReversal && !(int)$p['is_reversed'] && $user['role'] === 'owner'): ?>
                <a href="payment_reverse.php?id=<?= (int)$p['id'] ?>">Reverse</a>
            <?php elseif ((int)$p['is_reversed']): ?>
                <span class="muted">reversed</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>
<?php page_footer(); ?>
