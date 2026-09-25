<?php
/**
 * The page the centre owner actually opens every morning:
 * who is behind on their fees, and how to reach them.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$institutionId = current_institution_id();

$stmt = db()->prepare(
    'SELECT e.id AS enrolment_id, s.id AS student_id, s.first_name, s.last_name, s.phone,
            pr.name AS programme, e.intake_label, e.agreed_fee_fcfa,
            ' . PAID_SUM_SQL . ' AS paid,
            (e.agreed_fee_fcfa - ' . PAID_SUM_SQL . ') AS owing
       FROM enrolments e
       JOIN students   s  ON s.id  = e.student_id
       JOIN programmes pr ON pr.id = e.programme_id
       LEFT JOIN payments p ON p.enrolment_id = e.id
      WHERE e.institution_id = ? AND e.status = \'active\'
      GROUP BY e.id
     HAVING paid < e.agreed_fee_fcfa
      ORDER BY owing DESC'
);
$stmt->execute([$institutionId]);
$rows = $stmt->fetchAll();

$totalOwed = 0;
foreach ($rows as $row) {
    $totalOwed += (int)$row['agreed_fee_fcfa'] - (int)$row['paid'];
}

page_header('Who owes');
?>
<h1>Who owes</h1>
<div class="tiles">
    <div class="tile"><div class="label">Students behind</div><div class="value"><?= count($rows) ?></div></div>
    <div class="tile"><div class="label">Total outstanding</div><div class="value"><?= e(money($totalOwed)) ?></div></div>
</div>

<?php if (!$rows): ?>
    <p class="flash">Nobody is behind on fees. Everything is collected.</p>
<?php else: ?>
<div class="table-scroll">
<table>
    <tr>
        <th>Student</th><th>Phone</th><th>Programme</th>
        <th class="num">Fee</th><th class="num">Paid</th><th class="num">Owing</th><th></th>
    </tr>
    <?php foreach ($rows as $row):
        $owing = (int)$row['agreed_fee_fcfa'] - (int)$row['paid']; ?>
    <tr>
        <td><a href="student_view.php?id=<?= (int)$row['student_id'] ?>"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></a></td>
        <td><?= e($row['phone']) ?></td>
        <td><?= e($row['programme']) ?><?= $row['intake_label'] !== '' ? ' · ' . e($row['intake_label']) : '' ?></td>
        <td class="num"><?= e(money((int)$row['agreed_fee_fcfa'])) ?></td>
        <td class="num"><?= e(money((int)$row['paid'])) ?></td>
        <td class="num"><span class="pill owing"><?= e(money($owing)) ?></span></td>
        <td><a href="payment_new.php?enrolment_id=<?= (int)$row['enrolment_id'] ?>">Record payment</a></td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>
<?php page_footer(); ?>
