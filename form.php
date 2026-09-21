<?php
declare(strict_types=1);

require_once "connection.php";
require_once "helpers.php";

$errors = [];
$values = ['first_name' => '', 'last_name' => '', 'email' => '', 'telephone' => ''];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    foreach ($values as $field => $_) {
        $values[$field] = trim((string)($_POST[$field] ?? ''));
    }

    if ($values['first_name'] === '') {
        $errors[] = 'First name is required.';
    }
    if ($values['last_name'] === '') {
        $errors[] = 'Last name is required.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($values['telephone'] === '') {
        $errors[] = 'Telephone is required.';
    }

    if (!$errors) {
        try {
            // Prepared statement: the values are sent separately from the SQL,
            // so no input can ever be parsed as SQL.
            $stmt = $conn->prepare(
                "INSERT INTO students (first_name, last_name, email, telephone)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param(
                'ssss',
                $values['first_name'],
                $values['last_name'],
                $values['email'],
                $values['telephone']
            );
            $stmt->execute();
            $stmt->close();

            // Post/Redirect/Get: stops a browser refresh from inserting twice.
            header("Location: table.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            error_log('Student insert failed: ' . $e->getMessage());
            $errors[] = 'Could not save the student. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student's Form</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Student Registration</h2>

        <?php if ($errors): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="form.php" method="post">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name"
                   value="<?php echo e($values['first_name']); ?>" required>

            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name"
                   value="<?php echo e($values['last_name']); ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?php echo e($values['email']); ?>" required>

            <label for="telephone">Telephone</label>
            <input type="tel" id="telephone" name="telephone"
                   value="<?php echo e($values['telephone']); ?>" required>

            <input type="submit" value="REGISTER">
        </form>
    </div>
</body>
</html>
