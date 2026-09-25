<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function session_start_secure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        // Enable once the site is served over HTTPS.
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT id, institution_id, full_name, role, password_hash
           FROM users
          WHERE email = ? AND is_active = 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always run password_verify, even for an unknown email: the dummy hash
    // keeps the response time similar so the form cannot be used to discover
    // which email addresses exist.
    $hash = is_array($user)
        ? $user['password_hash']
        : '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30M1OR5uwZzO';
    $ok = password_verify($password, $hash);
    if (!$ok || !is_array($user)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'             => (int)$user['id'],
        'institution_id' => (int)$user['institution_id'],
        'full_name'      => $user['full_name'],
        'role'           => $user['role'],
    ];
    return true;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login.php');
    }
    return $user;
}

/** The tenant every query must be scoped to. */
function current_institution_id(): int
{
    return require_login()['institution_id'];
}

function require_owner(): void
{
    if (require_login()['role'] !== 'owner') {
        http_response_code(403);
        exit('Only the centre owner can do this.');
    }
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
