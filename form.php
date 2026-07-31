<?php
include "connection.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telephone = mysqli_real_escape_string($conn, $_POST['telephone']);

    $sql = "INSERT INTO students(first_name, last_name, email, telephone) 
            VALUES('$first_name', '$last_name', '$email', '$telephone')";

    if (mysqli_query($conn, $sql)) {
        // Redirect to table.php after successful insert
        header("Location: table.php");
        exit();
    } else {
        echo mysqli_error($conn);
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
        <form action="form.php" method="post">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" required>
            
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" required>
            
            <label for="email">Email</label>
            <input type="email" name="email" required>
            
            <label for="telephone">Telephone</label>
            <input type="tel" name="telephone" required>
            
            <input type="submit" value="REGISTER">
        </form>
    </div>
</body>
</html>
