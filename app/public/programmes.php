<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
$institutionId = $user['institution_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_owner();
    $name     = trim((string)($_POST['name'] ?? ''));
    $duration = trim((string)($_POST['duration_label'] ?? ''));
    $feeRaw   = trim((string)($_POST['fee_fcfa'] ?? ''));

    if ($name === '')          { $errors[] = 'Programme name is required.'; }
    if (!ctype_digit($feeRaw)) { $errors[] = 'Fee must be a whole number of FCFA, with no spaces or decimals.'; }

    if (!$errors) {
        db()->prepare('INSERT INTO programmes (institution_id, name, fee_fcfa, duration_label) VALUES (?, ?, ?, ?)')
            ->execute([$institutionId, $name, (int)$feeRaw, $duration]);
        flash('Programme added.');
        redirect('programmes.php');
    }
}

$stmt = db()->prepare(
    'SELECT p.id, p.name, p.fee_fcfa, p.duration_label, p.is_active,
            COUNT(e.id) AS enrolled
       FROM programmes p
       LEFT JOIN enrolments e ON e.programme_id = p.id AND e.status = \'active\'
      WHERE p.institution_id = ?
      GROUP BY p.id
      ORDER BY p.is_active DESC, p.name'
);
$stmt->execute([$institutionId]);
$programmes = $stmt->fetchAll();

page_header('Programmes');
?>
<h1>Programmes</h1>

<?php if ($errors): ?>
    <div class="error"><?php foreach ($errors as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php if (!$programmes): ?>
    <p class="muted">No programmes yet. Add the courses this centre sells.</p>
<?php else: ?>
<div class="table-scroll">
<table>
    <tr><th>Programme</th><th>Duration</th><th class="num">Fee</th><th class="num">Active students</th></tr>
    <?php foreach ($programmes as $p): ?>
    <tr>
        <td><?= e($p['name']) ?> <?= $p['is_active'] ? '' : '<span class="pill">inactive</span>' ?></td>
        <td><?= e($p['duration_label']) ?></td>
        <td class="num"><?= e(money((int)$p['fee_fcfa'])) ?></td>
        <td class="num"><?= (int)$p['enrolled'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<?php if ($user['role'] === 'owner'): ?>
<h2>Add a programme</h2>
<div class="card">
<form method="post">
    <?= csrf_field() ?>
    <div class="row">
        <div>
            <label for="name">Name *</label>
            <input id="name" name="name" required placeholder="e.g. Category B driving licence">
        </div>
        <div>
            <label for="duration_label">Duration</label>
            <input id="duration_label" name="duration_label" placeholder="e.g. 3 months">
        </div>
        <div>
            <label for="fee_fcfa">Fee (FCFA) *</label>
            <input id="fee_fcfa" name="fee_fcfa" required inputmode="numeric" placeholder="150000">
        </div>
    </div>
    <button class="btn" type="submit">Add programme</button>
</form>
</div>
<?php endif; ?>
<?php page_footer(); ?>
