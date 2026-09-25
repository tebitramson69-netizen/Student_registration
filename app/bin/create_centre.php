<?php
/**
 * Create a centre and its owner account.
 * Run once per customer you onboard:
 *
 *   php bin/create_centre.php "Bright Future Computer Institute" "677000000" "Commercial Avenue, Bamenda" BFCI "Ramson Titih" ramson@example.com "a-strong-password"
 */
declare(strict_types=1);

require __DIR__ . '/../src/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script may only be run from the command line.\n");
}
if ($argc !== 8) {
    fwrite(STDERR, "Usage: php bin/create_centre.php <centre> <phone> <address> <receipt-prefix> <owner-name> <owner-email> <password>\n");
    exit(1);
}
[, $centre, $phone, $address, $prefix, $ownerName, $ownerEmail, $password] = $argv;

if (strlen($password) < 10) {
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}
if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Owner email is not valid.\n");
    exit(1);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO institutions (name, phone, address, receipt_prefix) VALUES (?, ?, ?, ?)')
        ->execute([$centre, $phone, $address, strtoupper(substr($prefix, 0, 8))]);
    $institutionId = (int)$pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO users (institution_id, full_name, email, password_hash, role)
         VALUES (?, ?, ?, ?, \'owner\')'
    )->execute([$institutionId, $ownerName, $ownerEmail, password_hash($password, PASSWORD_DEFAULT)]);

    $pdo->commit();
    printf("Created centre #%d (%s) with owner %s.\n", $institutionId, $centre, $ownerEmail);
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Failed: ' . $e->getMessage() . "\n");
    exit(1);
}
