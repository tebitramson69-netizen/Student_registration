<?php
/**
 * Delete a student. POST only.
 */

declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/students.php";

require_login();

// Deleting is a state change, so it must never happen on a GET. A link or an
// <img src="delete.php?id=5"> would otherwise destroy a record just because
// the signed-in admin's browser loaded it.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: table.php');
    exit();
}

require_csrf();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'No student was selected.');
} else {
    try {
        $student = find_student($conn, $id);

        if (delete_student($conn, $id)) {
            $name = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'The student';
            flash('success', $name . ' was deleted.');
        } else {
            flash('error', 'That student no longer exists.');
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Student delete failed: ' . $e->getMessage());
        flash('error', 'Could not delete the student. Please try again.');
    }
}

$conn->close();

header('Location: table.php');
exit();
