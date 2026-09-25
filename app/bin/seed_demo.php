<?php
/**
 * Demo data for showing FeeBook to a prospective customer.
 * NEVER run this against a real centre's database.
 */
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/payments.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

$institutionId = (int)($argv[1] ?? 0);
$userId        = (int)($argv[2] ?? 0);
if ($institutionId <= 0 || $userId <= 0) {
    fwrite(STDERR, "Usage: php bin/seed_demo.php <institution_id> <user_id>\n");
    exit(1);
}

$pdo = db();

$programmes = [
    ['Category B driving licence', 150000, '3 months'],
    ['Computer maintenance',       200000, '6 months'],
    ['Office applications',         90000, '2 months'],
];
$programmeIds = [];
foreach ($programmes as [$name, $fee, $duration]) {
    $pdo->prepare('INSERT INTO programmes (institution_id, name, fee_fcfa, duration_label) VALUES (?, ?, ?, ?)')
        ->execute([$institutionId, $name, $fee, $duration]);
    $programmeIds[] = (int)$pdo->lastInsertId();
}

$students = [
    ['Ayuk',    'Tabi',     '677112233', 'F'],
    ['Nfor',    'Bongwa',   '650445566', 'M'],
    ['Solange', 'Mbah',     '694778899', 'F'],
    ['Eric',    'Tchoumba', '677554433', 'M'],
    ['Brenda',  'Njoya',    '651223344', 'F'],
];

foreach ($students as $i => [$first, $last, $phone, $gender]) {
    $pdo->prepare(
        'INSERT INTO students (institution_id, student_code, first_name, last_name, phone, gender)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$institutionId, sprintf('S%04d', $i + 1), $first, $last, $phone, $gender]);
    $studentId = (int)$pdo->lastInsertId();

    $programmeId = $programmeIds[$i % count($programmeIds)];
    $fee = $programmes[$i % count($programmes)][1];

    $pdo->prepare(
        'INSERT INTO enrolments (institution_id, student_id, programme_id, intake_label, agreed_fee_fcfa, started_on)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$institutionId, $studentId, $programmeId, 'October 2026', $fee, date('Y-m-d', strtotime('-40 days'))]);
    $enrolmentId = (int)$pdo->lastInsertId();

    // Partial instalments, so the arrears page has something real to show.
    $instalments = [
        [intdiv($fee, 3), 'cash',   '-30 days', 'first instalment'],
        [intdiv($fee, 4), 'momo',   '-10 days', 'second instalment'],
    ];
    foreach (array_slice($instalments, 0, ($i % 2 === 0) ? 2 : 1) as [$amount, $method, $when, $note]) {
        record_payment($institutionId, $enrolmentId, $amount, $method, '',
                       date('Y-m-d', strtotime($when)), $note, $userId);
    }
}

echo "Demo data seeded for institution #{$institutionId}.\n";
