<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
$institutionId = $user['institution_id'];
$errors = [];

$enrolmentId = int_param($_GET, 'enrolment_id') ?? int_param($_POST, 'enrolment_id');
if ($enrolmentId === null) {
    redirect('students.php');
}

$stmt = db()->prepare(
    'SELECT e.id, e.agreed_fee_fcfa, e.intake_label, s.id AS student_id,
            s.first_name, s.last_name, pr.name AS programme,
            ' . PAID_SUM_SQL . ' AS paid
       FROM enrolments e
       JOIN students   s  ON s.id  = e.student_id
       JOIN programmes pr ON pr.id = e.programme_id
       LEFT JOIN payments p ON p.enrolment_id = e.id
      WHERE e.id = ? AND e.institution_id = ?
      GROUP BY e.id'
);
$stmt->execute([$enrolmentId, $institutionId]);
$enrolment = $stmt->fetch();
if (!$enrolment) {
    http_response_code(404);
    exit('Enrolment not found.');
}
$balance = (int)$enrolment['agreed_fee_fcfa'] - (int)$enrolment['paid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $amountRaw = trim((string)($_POST['amount_fcfa'] ?? ''));
    $method    = (string)($_POST['method'] ?? 'cash');
    $reference = trim((string)($_POST['reference'] ?? ''));
    $paidOn    = (string)($_POST['paid_on'] ?? '');
    $note      = trim((string)($_POST['note'] ?? ''));

    if (!ctype_digit($amountRaw) || (int)$amountRaw <= 0) {
        $errors[] = 'Amount must be a whole number of FCFA, greater than zero.';
    }
    if (!in_array($method, ['cash', 'momo', 'orange', 'bank', 'cheque'], true)) {
        $errors[] = 'Choose a payment method.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paidOn)) {
        $errors[] = 'Choose a valid payment date.';
    }

    if (!$errors) {
        try {
            $paymentId = record_payment($institutionId, $enrolmentId, (int)$amountRaw,
                                        $method, $reference, $paidOn, $note, $user['id']);
            flash('Payment recorded. Print or send this receipt to the student.');
            redirect('receipt.php?id=' . $paymentId);
        } catch (Throwable $ex) {
            $errors[] = 'Could not record the payment. Nothing was saved.';
        }
    }
}

page_header('Record a payment');
?>
<h1>Record a payment</h1>
<p class="muted">
    <?= e($enrolment['first_name'] . ' ' . $enrolment['last_name']) ?> ·
    <?= e($enrolment['programme']) ?>
    <?= $enrolment['intake_label'] !== '' ? ' · ' . e($enrolment['intake_label']) : '' ?>
</p>

<div class="tiles">
    <div class="tile"><div class="label">Agreed fee</div><div class="value"><?= e(money((int)$enrolment['agreed_fee_fcfa'])) ?></div></div>
    <div class="tile"><div class="label">Paid so far</div><div class="value"><?= e(money((int)$enrolment['paid'])) ?></div></div>
    <div class="tile"><div class="label">Balance</div><div class="value"><?= e(money($balance)) ?></div></div>
</div>

<?php if ($errors): ?>
    <div class="error"><?php foreach ($errors as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card">
<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="enrolment_id" value="<?= (int)$enrolmentId ?>">
    <div class="row">
        <div>
            <label for="amount_fcfa">Amount (FCFA) *</label>
            <input id="amount_fcfa" name="amount_fcfa" required inputmode="numeric"
                   value="<?= e((string)max(0, $balance)) ?>">
        </div>
        <div>
            <label for="method">Method *</label>
            <select id="method" name="method" required>
                <option value="cash">Cash</option>
                <option value="momo">MTN Mobile Money</option>
                <option value="orange">Orange Money</option>
                <option value="bank">Bank transfer</option>
                <option value="cheque">Cheque</option>
            </select>
        </div>
        <div>
            <label for="paid_on">Date *</label>
            <input id="paid_on" type="date" name="paid_on" required value="<?= e(date('Y-m-d')) ?>">
        </div>
    </div>
    <label for="reference">Reference</label>
    <input id="reference" name="reference" placeholder="MoMo transaction ID, cheque number, etc.">
    <label for="note">Note</label>
    <input id="note" name="note" placeholder="e.g. second instalment">
    <button class="btn" type="submit">Record payment and issue receipt</button>
    <a class="btn secondary" href="student_view.php?id=<?= (int)$enrolment['student_id'] ?>">Cancel</a>
</form>
</div>
<?php page_footer(); ?>
