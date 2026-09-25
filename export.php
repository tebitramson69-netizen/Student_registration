<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/helpers.php";
require_once __DIR__ . "/includes/students.php";
require_login();

// Export what is on screen. Downloading the whole table from a filtered view
// silently hands back rows the admin did not ask for.
$search = trim((string)($_GET['search'] ?? ''));

try {
    $result = stream_students($conn, $search);
} catch (mysqli_sql_exception $e) {
    error_log('Student export failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not export the student list. Please try again later.');
}

// Force a download once the query has succeeded, so an error page is never
// sent with CSV headers already attached.
header('Content-Type: text/csv; charset=utf-8');
$filename = $search === '' ? 'students.csv' : 'students-filtered.csv';
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Telephone']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, array_map('csv_safe', $row));
}

fclose($output);
$result->free();
$conn->close();
exit();
