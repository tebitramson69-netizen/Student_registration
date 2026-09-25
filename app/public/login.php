<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

if (current_user()) {
    redirect('index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } elseif (attempt_login($email, $password)) {
        redirect('index.php');
    } else {
        $error = 'Wrong email or password.';
    }
}

page_header('Sign in', false);
?>
<div class="card" style="max-width:380px;margin:60px auto;">
    <h1>FeeBook</h1>
    <p class="muted">Sign in to your centre.</p>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required autofocus
               value="<?= e($_POST['email'] ?? '') ?>">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>
        <button class="btn" type="submit">Sign in</button>
    </form>
</div>
<?php page_footer(); ?>
