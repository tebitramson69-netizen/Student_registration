<?php
/**
 * Reversing a payment writes a compensating entry — it never edits or deletes
 * the original. The owner must give a reason, and both entries stay visible.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
require_owner();
$institutionId = $user['institution_id'];

$id = int_param($_GET, 'id') ?? int_param($_POST, 'id');
if ($id === null) {
    redirect('index.php');
}

$stmt = db()->prepare(
    'SELECT p.id, p.receipt_no, p.amount_fcfa, p.paid_on, p.entry_type,
            s.id AS student_id, s.first_name, s.last_name,
            (SELECT COUNT(*) FROM payments r WHERE r.reverses_payment_id = p.id) AS is_reversed
       FROM payments p
       JOIN enrolments e ON e.id = p.enrolment_id
       JOIN students   s ON s.id = e.student_id
      WHERE p.id = ? AND p.institution_id = ?'
);
$stmt->execute([$id, $institutionId]);
$payment = $stmt->fetch();

if (!$payment || $payment['entry_type'] !== 'payment') {
    http_response_code(404);
    exit('Payment not found.');
}
if ((int)$payment['is_reversed'] > 0) {
    flash('That payment was already reversed.');
    redirect('student_view.php?id=' . (int)$payment['student_id']);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $reason = trim((string)($_POST['reason'] ?? ''));
    if ($reason === '') {
        $errors[] = 'Give a reason. It stays on the record permanently.';
    } else {
        try {
            reverse_payment($institutionId, $id, $reason, $user['id']);
            flash('Payment reversed. The original receipt is kept for the record.');
            redirect('student_view.php?id=' . (int)$payment['student_id']);
        } catch (Throwable $ex) {
            $errors[] = 'Could not reverse the payment. Nothing was changed.';
        }
    }
}

page_header('Reverse payment');
?>
<h1>Reverse receipt <?= e($payment['receipt_no']) ?></h1>
<p class="muted">
    <?= e(money((int)$payment['amount_fcfa'])) ?> from
    <?= e($payment['first_name'] . ' ' . $payment['last_name']) ?> on <?= e($payment['paid_on']) ?>.
</p>
<p>The original receipt is never deleted. A reversal entry is added beside it so
   the history stays complete and can be explained to the student.</p>

<?php if ($errors): ?>
    <div class="error"><?php foreach ($errors as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card">
<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <label for="reason">Reason *</label>
    <input id="reason" name="reason" required placeholder="e.g. entered twice by mistake">
    <button class="btn danger" type="submit">Reverse this payment</button>
    <a class="btn secondary" href="student_view.php?id=<?= (int)$payment['student_id'] ?>">Cancel</a>
</form>
</div>
<?php page_footer(); ?>
