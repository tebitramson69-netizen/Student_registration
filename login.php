<?php
declare(strict_types=1);

require_once "auth.php";

if (is_logged_in()) {
    header("Location: table.php");
    exit();
}

$errors   = [];
$username = '';
$notice   = isset($_GET['timeout']) ? 'Your session expired. Please sign in again.' : '';

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    require_csrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $ip       = client_ip();

    try {
        if ($username === '' || $password === '') {
            $errors[] = 'Username and password are required.';
        } elseif (strlen($username) > 50) {
            // Longer than admins.username holds, so it cannot be a real
            // account. Rejecting it here keeps record_failed_login() from
            // throwing on the oversized value and skipping the throttle.
            $errors[] = 'Invalid username or password.';
        } elseif (login_is_locked($conn, $username, $ip)) {
            $errors[] = 'Too many failed attempts. Please wait 15 minutes and try again.';
        } else {
            $stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Always run a verification, even with no such user, so the reply
            // takes the same time either way (see LOGIN_DUMMY_HASH).
            $hash  = $admin['password_hash'] ?? LOGIN_DUMMY_HASH;
            $valid = password_verify($password, $hash) && $admin !== null;

            if ($valid) {
                // Upgrade the stored hash if PHP's default cost has since risen.
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $rehash  = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                    $rehash->bind_param('si', $newHash, $admin['id']);
                    $rehash->execute();
                    $rehash->close();
                }

                clear_login_attempts($conn, $username, $ip);
                login_admin((int)$admin['id'], (string)$admin['username']);

                header("Location: table.php");
                exit();
            }

            record_failed_login($conn, $username, $ip);
            // One message for both cases: naming which half was wrong would
            // confirm to an attacker that a username exists.
            $errors[] = 'Invalid username or password.';
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Login failed: ' . $e->getMessage());
        $errors[] = 'Could not sign you in right now. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Admin Sign In</h2>

        <?php if ($notice !== ''): ?>
            <p class="notice"><?php echo e($notice); ?></p>
        <?php endif; ?>

        <?php if ($errors): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="login.php" method="post">
            <?php echo csrf_field(); ?>

            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?php echo e($username); ?>" autocomplete="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required>

            <input type="submit" value="SIGN IN">
        </form>
    </div>
</body>
</html>
