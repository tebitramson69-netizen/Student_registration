<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/layout.php";

if (is_logged_in()) {
    header('Location: index.php');
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

                header('Location: index.php');
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

render_header('Sign in', '', true);
?>

<section class="card">
    <div class="card__body">
        <h1 class="mb-1">Student Registry</h1>
        <p class="lede">Sign in to manage student records.</p>

        <?php if ($notice !== ''): ?>
            <div class="flash flash--info mb-4" role="status">
                <span class="flash__icon" aria-hidden="true">&#8505;</span>
                <span><strong>Note.</strong> <?php echo e($notice); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="flash flash--error mb-4" role="alert">
                <span class="flash__icon" aria-hidden="true">&#9888;</span>
                <span>
                    <strong>Error.</strong>
                    <?php echo e($errors[0]); ?>
                </span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <?php echo csrf_field(); ?>

            <div class="field">
                <label for="username">Username</label>
                <input class="input" type="text" id="username" name="username"
                       value="<?php echo e($username); ?>"
                       autocomplete="username" autofocus required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input class="input" type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>

            <button class="button w-full" type="submit">Sign in</button>
        </form>
    </div>
</section>

<?php render_footer(); ?>
