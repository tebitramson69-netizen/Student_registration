<?php
include "connection.php";

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=students.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Telephone']);

// Fetch all students
$sql = "SELECT * FROM students ORDER BY first_name ASC, last_name ASC";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}

fclose($output);
mysqli_close($conn);
exit();
?>
