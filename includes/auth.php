<?php
/**
 * Authentication, session handling and CSRF protection.
 *
 * Every page that touches student data includes this file and calls
 * require_login() before producing any output.
 */

declare(strict_types=1);

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/helpers.php';

/** Log the admin out after this many seconds of inactivity. */
const SESSION_IDLE_TIMEOUT = 1800;

/** Failed logins allowed per username+IP before the pair is locked out. */
const LOGIN_MAX_ATTEMPTS = 5;

/** How long that lockout lasts, in seconds. */
const LOGIN_LOCKOUT_SECONDS = 900;

/**
 * A valid bcrypt hash of a random string, verified against when the username
 * does not exist. Without it a missing user returns noticeably faster than a
 * wrong password, which lets an attacker enumerate valid usernames.
 */
const LOGIN_DUMMY_HASH = '$2y$12$qaIclb/iOTTKtYreBQIGlOxv.w8MSh8yEdwY0K0PbTgTsjrDf7Cam';

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie before the session is created.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,  // JavaScript cannot read the session id
        'samesite' => 'Lax', // the cookie is not sent on a cross-site POST
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function current_admin_username(): string
{
    return (string)($_SESSION['admin_username'] ?? '');
}

/**
 * Stop the request unless an admin is signed in and still active.
 * Must be called before any output is sent, because it may redirect.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }

    if (isset($_SESSION['last_activity'])
        && (time() - (int)$_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        logout_admin();
        header('Location: login.php?timeout=1');
        exit();
    }

    $_SESSION['last_activity'] = time();
}

/**
 * The same guard for a JSON endpoint.
 *
 * require_login() redirects to the sign-in page, which an XHR would receive as
 * a 200 containing HTML. An API answers 401 so the caller can react.
 */
function require_login_json(): void
{
    if (!is_logged_in()
        || (isset($_SESSION['last_activity'])
            && (time() - (int)$_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT)) {
        if (is_logged_in()) {
            logout_admin();
        }

        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Your session has expired. Please sign in again.']);
        exit();
    }

    $_SESSION['last_activity'] = time();
}

function login_admin(int $id, string $username): void
{
    // A new session id on privilege change defeats session fixation: an id
    // planted before login is no longer the one carrying the credentials.
    session_regenerate_id(true);

    $_SESSION['admin_id']       = $id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['last_activity']  = time();
    unset($_SESSION['csrf_token']); // issue a fresh token for the new session
}

function logout_admin(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/* ---------------------------------------------------------------------------
 * CSRF protection
 *
 * Every state-changing request must carry a token that only a page served by
 * this site could know, so a form on an attacker's site cannot make the
 * admin's browser create, edit or delete a record.
 * ------------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Stop the request unless the POST carries this session's token. */
function require_csrf(): void
{
    $submitted = (string)($_POST['csrf_token'] ?? '');

    // hash_equals compares in constant time, so the token cannot be guessed
    // one character at a time by measuring how long the comparison takes.
    if ($submitted === ''
        || empty($_SESSION['csrf_token'])
        || !hash_equals((string)$_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        exit('Invalid or expired form submission. Please go back and try again.');
    }
}

/* ---------------------------------------------------------------------------
 * Brute-force throttling
 * ------------------------------------------------------------------------ */

function client_ip(): string
{
    // REMOTE_ADDR only. Headers such as X-Forwarded-For are attacker-supplied
    // unless a trusted proxy sets them, and would let anyone reset the count.
    return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function login_is_locked(mysqli $conn, string $username, string $ip): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS failures
         FROM login_attempts
         WHERE username = ? AND ip_address = ?
           AND attempted_at > (NOW() - INTERVAL ? SECOND)"
    );
    $seconds = LOGIN_LOCKOUT_SECONDS;
    $stmt->bind_param('ssi', $username, $ip, $seconds);
    $stmt->execute();
    $failures = (int)$stmt->get_result()->fetch_assoc()['failures'];
    $stmt->close();

    return $failures >= LOGIN_MAX_ATTEMPTS;
}

function record_failed_login(mysqli $conn, string $username, string $ip): void
{
    $stmt = $conn->prepare(
        "INSERT INTO login_attempts (username, ip_address, attempted_at) VALUES (?, ?, NOW())"
    );
    $stmt->bind_param('ss', $username, $ip);
    $stmt->execute();
    $stmt->close();
}

function clear_login_attempts(mysqli $conn, string $username, string $ip): void
{
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ? AND ip_address = ?");
    $stmt->bind_param('ss', $username, $ip);
    $stmt->execute();
    $stmt->close();
}
