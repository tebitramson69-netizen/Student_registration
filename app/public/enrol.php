<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
$institutionId = $user['institution_id'];
$errors = [];

$studentId = int_param($_GET, 'student_id') ?? int_param($_POST, 'student_id');
if ($studentId === null) {
    redirect('students.php');
}

$stmt = db()->prepare('SELECT id, first_name, last_name FROM students WHERE id = ? AND institution_id = ?');
$stmt->execute([$studentId, $institutionId]);
$student = $stmt->fetch();
if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

$programmesStmt = db()->prepare(
    'SELECT id, name, fee_fcfa FROM programmes WHERE institution_id = ? AND is_active = 1 ORDER BY name'
);
$programmesStmt->execute([$institutionId]);
$programmes = $programmesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $programmeId = int_param($_POST, 'programme_id');
    $intake      = trim((string)($_POST['intake_label'] ?? ''));
    $startedOn   = (string)($_POST['started_on'] ?? '');
    $feeRaw      = trim((string)($_POST['agreed_fee_fcfa'] ?? ''));

    // Confirm the programme belongs to this centre before trusting the id.
    $check = db()->prepare('SELECT fee_fcfa FROM programmes WHERE id = ? AND institution_id = ?');
    $check->execute([$programmeId, $institutionId]);
    $programmeFee = $check->fetchColumn();

    if ($programmeFee === false)  { $errors[] = 'Choose a programme.'; }
    if (!ctype_digit($feeRaw))    { $errors[] = 'Agreed fee must be a whole number of FCFA.'; }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startedOn)) { $errors[] = 'Choose a valid start date.'; }

    if (!$errors) {
        db()->prepare(
            'INSERT INTO enrolments (institution_id, student_id, programme_id, intake_label, agreed_fee_fcfa, started_on)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$institutionId, $studentId, $programmeId, $intake, (int)$feeRaw, $startedOn]);
        flash('Enrolled. You can now record payments against this programme.');
        redirect('student_view.php?id=' . $studentId);
    }
}

page_header('Enrol student');
?>
<h1>Enrol <?= e($student['first_name'] . ' ' . $student['last_name']) ?></h1>

<?php if ($errors): ?>
    <div class="error"><?php foreach ($errors as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php if (!$programmes): ?>
    <p class="error">Add a programme first.</p>
    <a class="btn" href="programmes.php">Go to programmes</a>
<?php else: ?>
<div class="card">
<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="student_id" value="<?= (int)$studentId ?>">
    <label for="programme_id">Programme *</label>
    <select id="programme_id" name="programme_id" required onchange="
        var o = this.options[this.selectedIndex];
        document.getElementById('agreed_fee_fcfa').value = o.dataset.fee || '';">
        <option value="">— choose —</option>
        <?php foreach ($programmes as $p): ?>
            <option value="<?= (int)$p['id'] ?>" data-fee="<?= (int)$p['fee_fcfa'] ?>">
                <?= e($p['name']) ?> — <?= e(money((int)$p['fee_fcfa'])) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="row">
        <div>
            <label for="agreed_fee_fcfa">Agreed fee (FCFA) *</label>
            <input id="agreed_fee_fcfa" name="agreed_fee_fcfa" required inputmode="numeric">
            <small class="muted">Change it if this student negotiated a different price.</small>
        </div>
        <div>
            <label for="intake_label">Intake</label>
            <input id="intake_label" name="intake_label" placeholder="e.g. October 2026">
        </div>
        <div>
            <label for="started_on">Start date *</label>
            <input id="started_on" type="date" name="started_on" required value="<?= e(date('Y-m-d')) ?>">
        </div>
    </div>
    <button class="btn" type="submit">Enrol student</button>
    <a class="btn secondary" href="student_view.php?id=<?= (int)$studentId ?>">Cancel</a>
</form>
</div>
<?php endif; ?>
<?php page_footer(); ?>
