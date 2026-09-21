<?php
declare(strict_types=1);

require_once "connection.php";

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

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Telephone']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$stmt->close();
$conn->close();
exit();
