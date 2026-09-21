<?php
declare(strict_types=1);

require_once "connection.php";

$id = (int)($_GET['id'] ?? 0);

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
