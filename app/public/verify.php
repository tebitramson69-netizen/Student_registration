<?php
/**
 * Public receipt verification — no login required.
 * A student holding a paper receipt can confirm it was really issued.
 * Only the minimum is shown: enough to prove the receipt, nothing more.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$code   = strtoupper(trim((string)($_GET['code'] ?? '')));
$result = null;
$searched = $code !== '';

if ($searched && preg_match('/^[0-9A-F]{8}$/', $code)) {
    $stmt = db()->prepare(
        'SELECT p.receipt_no, p.amount_fcfa, p.paid_on, p.entry_type,
                s.first_name, s.last_name, i.name AS centre_name,
                (SELECT COUNT(*) FROM payments r WHERE r.reverses_payment_id = p.id) AS is_reversed
           FROM payments p
           JOIN enrolments   e ON e.id = p.enrolment_id
           JOIN students     s ON s.id = e.student_id
           JOIN institutions i ON i.id = p.institution_id
          WHERE p.receipt_code = ?'
    );
    $stmt->execute([$code]);
    $result = $stmt->fetch() ?: null;
}

page_header('Verify a receipt', false);
?>
<div class="card" style="max-width:460px;margin:40px auto;">
    <h1>Verify a receipt</h1>
    <p class="muted">Enter the 8-character code printed on your receipt.</p>
    <form method="get">
        <label for="code">Verification code</label>
        <input id="code" name="code" required maxlength="8" value="<?= e($code) ?>" placeholder="A1B2C3D4">
        <button class="btn" type="submit">Check</button>
    </form>

    <?php if ($searched): ?>
        <?php if ($result === null): ?>
            <p class="error" style="margin-top:18px;">No receipt found with that code.</p>
        <?php elseif ((int)$result['is_reversed'] > 0 || $result['entry_type'] === 'reversal'): ?>
            <p class="error" style="margin-top:18px;">
                This receipt was issued but has since been reversed. Ask the centre about it.
            </p>
        <?php else: ?>
            <p class="flash" style="margin-top:18px;">This receipt is genuine.</p>
            <dl class="receipt" style="border:0;padding:0;margin:0;">
                <dt>Centre</dt><dd><?= e($result['centre_name']) ?></dd>
                <dt>Receipt no.</dt><dd><?= e($result['receipt_no']) ?></dd>
                <dt>Student</dt><dd><?= e($result['first_name'] . ' ' . $result['last_name']) ?></dd>
                <dt>Amount</dt><dd><?= e(money((int)$result['amount_fcfa'])) ?></dd>
                <dt>Date</dt><dd><?= e($result['paid_on']) ?></dd>
            </dl>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php page_footer(); ?>
