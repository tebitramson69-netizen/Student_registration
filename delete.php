<?php
declare(strict_types=1);

require_once "auth.php";
require_login();

// Deleting is a state change, so it must never happen on a GET. A link or an
// <img src="delete.php?id=5"> would otherwise destroy a record just because
// the signed-in admin's browser loaded it.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: table.php");
    exit();
}

require_csrf();

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Student delete failed: ' . $e->getMessage());
    }
}

$conn->close();

header("Location: table.php");
exit();
