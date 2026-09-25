<?php
/**
 * CSV of collections, for the owner's own records and their accountant.
 * Scoped to the signed-in centre and optionally to a month.
 */
require __DIR__ . '/_bootstrap.php';

$institutionId = current_institution_id();

$month  = (string)($_GET['month'] ?? '');
$where  = 'WHERE p.institution_id = ?';
$params = [$institutionId];
if (preg_match('/^\d{4}-\d{2}$/', $month)) {
    $where .= " AND DATE_FORMAT(p.paid_on, '%Y-%m') = ?";
    $params[] = $month;
}

$stmt = db()->prepare(
    "SELECT p.receipt_no, p.paid_on, p.entry_type, p.amount_fcfa, p.method, p.reference, p.note,
            s.student_code, s.first_name, s.last_name, s.phone,
            pr.name AS programme, e.intake_label, u.full_name AS recorded_by
       FROM payments p
       JOIN enrolments   e  ON e.id  = p.enrolment_id
       JOIN students     s  ON s.id  = e.student_id
       JOIN programmes   pr ON pr.id = e.programme_id
       LEFT JOIN users   u  ON u.id  = p.recorded_by
       $where
      ORDER BY p.paid_on, p.id"
);
$stmt->execute($params);

$filename = 'collections' . ($month !== '' ? '-' . $month : '') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// BOM so Excel opens accented names correctly.
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Receipt', 'Date', 'Type', 'Amount FCFA', 'Method', 'Reference', 'Note',
               'Student code', 'First name', 'Last name', 'Phone',
               'Programme', 'Intake', 'Recorded by']);

while ($row = $stmt->fetch()) {
    // A reversal is written as a negative number so the column sums correctly.
    $amount = (int)$row['amount_fcfa'] * ($row['entry_type'] === 'reversal' ? -1 : 1);
    fputcsv($out, [
        $row['receipt_no'], $row['paid_on'], $row['entry_type'], $amount,
        $row['method'], $row['reference'], $row['note'],
        $row['student_code'], $row['first_name'], $row['last_name'], $row['phone'],
        $row['programme'], $row['intake_label'], $row['recorded_by'],
    ]);
}
fclose($out);
