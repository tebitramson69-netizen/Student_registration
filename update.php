<?php
declare(strict_types=1);

require_once "connection.php";
require_once "helpers.php";

$errors  = [];
$student = null;

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $id = (int)($_POST['id'] ?? 0);

    // Keep the submitted values so the form can be redisplayed on error.
    $student = [
        'id'         => $id,
        'first_name' => trim((string)($_POST['first_name'] ?? '')),
        'last_name'  => trim((string)($_POST['last_name'] ?? '')),
        'email'      => trim((string)($_POST['email'] ?? '')),
        'telephone'  => trim((string)($_POST['telephone'] ?? '')),
    ];

    if ($id <= 0) {
        $errors[] = 'Invalid student.';
    }
    if ($student['first_name'] === '') {
        $errors[] = 'First name is required.';
    }
    if ($student['last_name'] === '') {
        $errors[] = 'Last name is required.';
    }
    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($student['telephone'] === '') {
        $errors[] = 'Telephone is required.';
    }

    if (!$errors) {
        try {
            $stmt = $conn->prepare(
                "UPDATE students
                 SET first_name = ?, last_name = ?, email = ?, telephone = ?
                 WHERE id = ?"
            );
            $stmt->bind_param(
                'ssssi',
                $student['first_name'],
                $student['last_name'],
                $student['email'],
                $student['telephone'],
                $student['id']
            );
            $stmt->execute();
            $stmt->close();

            header("Location: table.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            error_log('Student update failed: ' . $e->getMessage());
            $errors[] = 'Could not update the student. Please try again.';
        }
    }
} else {
    // Loading the form: fetch the student being edited.
    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare(
            "SELECT id, first_name, last_name, email, telephone FROM students WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    // No id, or no such student: nothing to edit, so go back to the list
    // instead of rendering a form over undefined values.
    if (!$student) {
        header("Location: table.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Update Student</h2>

    <?php if ($errors): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="update.php" method="post">
        <input type="hidden" name="id" value="<?php echo (int)$student['id']; ?>">

        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name"
               value="<?php echo e($student['first_name']); ?>" required><br>

        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name"
               value="<?php echo e($student['last_name']); ?>" required><br>

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?php echo e($student['email']); ?>" required><br>

        <label for="telephone">Telephone</label>
        <input type="tel" id="telephone" name="telephone"
               value="<?php echo e($student['telephone']); ?>" required><br>

        <input type="submit" value="Update">
    </form>

    <p><a href="table.php">Back to list</a></p>
</body>
</html>
