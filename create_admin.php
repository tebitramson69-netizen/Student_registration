<?php
/**
 * Creates an admin account. Run it from the command line:
 *
 *     php create_admin.php
 *
 * It is deliberately not reachable over HTTP - a web-facing "create the first
 * admin" page is a standing invitation to whoever finds it first.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

require_once __DIR__ . '/connection.php';

const MIN_PASSWORD_LENGTH = 10;

function prompt(string $label): string
{
    fwrite(STDOUT, $label);

    return trim((string)fgets(STDIN));
}

/**
 * Strip only the line ending, never surrounding spaces: login.php compares
 * the password exactly as posted, so trimming here would store a different
 * password from the one the admin believes they set.
 */
function read_secret_line(): string
{
    return rtrim((string)fgets(STDIN), "\r\n");
}

/**
 * Read a password without printing it. stty exists on Linux and macOS; on
 * Windows it does not, so the input is read normally and the user is warned.
 */
function prompt_secret(string $label): string
{
    $hasStty = stripos(PHP_OS_FAMILY, 'Windows') === false
        && shell_exec('command -v stty 2>/dev/null') !== null;

    if (!$hasStty) {
        fwrite(STDOUT, "(Your password will be visible as you type.)\n");
        fwrite(STDOUT, $label);
        return read_secret_line();
    }

    fwrite(STDOUT, $label);
    shell_exec('stty -echo');
    $secret = read_secret_line();
    shell_exec('stty echo');
    fwrite(STDOUT, "\n");

    return $secret;
}

$username = prompt('Username: ');

if ($username === '' || strlen($username) > 50) {
    fwrite(STDERR, "Username must be between 1 and 50 characters.\n");
    exit(1);
}

$password = prompt_secret('Password: ');
$confirm  = prompt_secret('Confirm password: ');

if ($password !== $confirm) {
    fwrite(STDERR, "The passwords do not match.\n");
    exit(1);
}

if (strlen($password) < MIN_PASSWORD_LENGTH) {
    fwrite(STDERR, 'Password must be at least ' . MIN_PASSWORD_LENGTH . " characters.\n");
    exit(1);
}

try {
    $stmt = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
    // password_hash() salts each hash itself; never hash a password with md5,
    // sha1 or any plain digest.
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param('ss', $username, $hash);
    $stmt->execute();
    $stmt->close();

    fwrite(STDOUT, "Admin '$username' created.\n");
} catch (mysqli_sql_exception $e) {
    // 1062 is MySQL's duplicate-key error, raised by uniq_admins_username.
    if ($e->getCode() === 1062) {
        fwrite(STDERR, "An admin with that username already exists.\n");
        exit(1);
    }

    fwrite(STDERR, 'Could not create the admin: ' . $e->getMessage() . "\n");
    exit(1);
}

$conn->close();
