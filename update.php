<?php
include "connection.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM students WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $student = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $id = intval($_POST['id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telephone = mysqli_real_escape_string($conn, $_POST['telephone']);

    $sql = "UPDATE students 
            SET first_name='$first_name', last_name='$last_name', email='$email', telephone='$telephone' 
            WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        header("Location: table.php");
        exit();
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student</title>
</head>
<body>
    <h2>Update Student</h2>
    <form action="update.php" method="post">
        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
        
        <label>First Name</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required><br>
        
        <label>Last Name</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required><br>
        
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required><br>
        
        <label>Telephone</label>
        <input type="tel" name="telephone" value="<?php echo htmlspecialchars($student['telephone']); ?>" required><br>
        
        <input type="submit" value="Update">
    </form>
</body>
</html>
