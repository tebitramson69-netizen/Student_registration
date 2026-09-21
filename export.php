<?php
declare(strict_types=1);

require_once "auth.php";
require_login();

// Select the columns explicitly so the CSV rows always line up with the header
// below, whatever order the table happens to declare them in.
try {
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, email, telephone
         FROM students
         ORDER BY first_name ASC, last_name ASC"
    );
    $stmt->execute();
    $result = $stmt->get_result();
} catch (mysqli_sql_exception $e) {
    error_log('Student export failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not export the student list. Please try again later.');
}

// Force a download once the query has succeeded, so an error page is never
// sent with CSV headers already attached.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=students.csv');

/**
 * A cell starting with =, +, - or @ is treated as a formula by Excel and
 * LibreOffice when the CSV is opened, so a student named "=cmd|..." would run
 * as one. Prefixing a single quote keeps the cell as text.
 */
function csv_safe(string|int|float|null $value): string
{
    $value = (string)$value;

    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }

    return $value;
}

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Telephone']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, array_map('csv_safe', $row));
}

fclose($output);
$stmt->close();
$conn->close();
exit();
