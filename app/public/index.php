<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
$institutionId = $user['institution_id'];

$centre = db()->prepare('SELECT name FROM institutions WHERE id = ?');
$centre->execute([$institutionId]);
$centreName = (string)$centre->fetchColumn();

// Expected = sum of agreed fees on active enrolments.
// Collected = net of payments and reversals.
// Native prepared statements do not allow the same named placeholder twice,
// so each subquery gets its own parameter.
$totals = db()->prepare(
    'SELECT
        (SELECT COUNT(*) FROM students WHERE institution_id = :i1)                        AS students,
        (SELECT COUNT(*) FROM enrolments WHERE institution_id = :i2 AND status = \'active\') AS active_enrolments,
        (SELECT COALESCE(SUM(agreed_fee_fcfa),0) FROM enrolments
          WHERE institution_id = :i3 AND status = \'active\')                             AS expected,
        (SELECT COALESCE(SUM(CASE WHEN entry_type = \'payment\' THEN amount_fcfa ELSE -amount_fcfa END),0)
           FROM payments WHERE institution_id = :i4)                                      AS collected,
        (SELECT COALESCE(SUM(CASE WHEN entry_type = \'payment\' THEN amount_fcfa ELSE -amount_fcfa END),0)
           FROM payments WHERE institution_id = :i5
            AND YEAR(paid_on) = YEAR(CURDATE()) AND MONTH(paid_on) = MONTH(CURDATE()))    AS this_month'
);
$totals->execute([
    ':i1' => $institutionId, ':i2' => $institutionId, ':i3' => $institutionId,
    ':i4' => $institutionId, ':i5' => $institutionId,
]);
$t = $totals->fetch();

$recent = db()->prepare(
    'SELECT p.id, p.receipt_no, p.amount_fcfa, p.method, p.paid_on, p.entry_type,
            s.first_name, s.last_name
       FROM payments p
       JOIN enrolments e ON e.id = p.enrolment_id
       JOIN students   s ON s.id = e.student_id
      WHERE p.institution_id = ?
      ORDER BY p.id DESC
      LIMIT 10'
);
$recent->execute([$institutionId]);
$recentPayments = $recent->fetchAll();

page_header('Dashboard');
?>
<h1><?= e($centreName) ?></h1>

<div class="tiles">
    <div class="tile"><div class="label">Students</div><div class="value"><?= (int)$t['students'] ?></div></div>
    <div class="tile"><div class="label">Active enrolments</div><div class="value"><?= (int)$t['active_enrolments'] ?></div></div>
    <div class="tile"><div class="label">Collected this month</div><div class="value"><?= e(money((int)$t['this_month'])) ?></div></div>
    <div class="tile"><div class="label">Still owed</div><div class="value"><?= e(money(max(0, (int)$t['expected'] - (int)$t['collected']))) ?></div></div>
</div>

<p>
    <a class="btn" href="student_form.php">Register a student</a>
    <a class="btn secondary" href="arrears.php">See who owes</a>
    <a class="btn secondary" href="export.php">Download collections (CSV)</a>
</p>

<h2>Latest receipts</h2>
<?php if (!$recentPayments): ?>
    <p class="muted">No payments recorded yet.</p>
<?php else: ?>
<div class="table-scroll">
<table>
    <tr><th>Receipt</th><th>Student</th><th>Date</th><th>Method</th><th class="num">Amount</th></tr>
    <?php foreach ($recentPayments as $p): ?>
    <tr>
        <td><a href="receipt.php?id=<?= (int)$p['id'] ?>"><?= e($p['receipt_no']) ?></a></td>
        <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
        <td><?= e($p['paid_on']) ?></td>
        <td><?= e($p['method']) ?></td>
        <td class="num<?= $p['entry_type'] === 'reversal' ? ' reversed' : '' ?>">
            <?= $p['entry_type'] === 'reversal' ? '-' : '' ?><?= e(money((int)$p['amount_fcfa'])) ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>
<?php page_footer(); ?>
