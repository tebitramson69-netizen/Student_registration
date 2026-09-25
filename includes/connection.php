<?php
/**
 * Database connection.
 *
 * Credentials are read from config.php when that file exists. config.php is
 * git-ignored, so real credentials never enter version control. When it is
 * absent the local XAMPP defaults below are used, which keeps a fresh clone
 * working out of the box on a development machine.
 *
 * Copy includes/config.example.php to includes/config.php and edit it before deploying.
 */

declare(strict_types=1);

// Make mysqli throw exceptions instead of emitting warnings and returning false,
// so a failed query can never silently continue with a false result.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$config = [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'student_db',
];

if (is_file(__DIR__ . '/config.php')) {
    $fileConfig = require __DIR__ . '/config.php';

    // config.php must `return` an array. A file written in the older
    // variable-assignment style makes require yield int(1), which would
    // otherwise fatal here, outside the try block below.
    if (!is_array($fileConfig)) {
        error_log('config.php must return an array of settings.');
        fail_startup('Server configuration error. Please contact the administrator.');
    }

    // A typo such as 'databse' would otherwise fall back to the local
    // development defaults, including the empty root password, on a server
    // the admin believes is configured.
    $unknown = array_diff_key($fileConfig, $config);
    if ($unknown) {
        error_log('Unknown config.php keys: ' . implode(', ', array_keys($unknown)));
        fail_startup('Server configuration error. Please contact the administrator.');
    }

    $config = array_merge($config, $fileConfig);
}

/**
 * Abort before the application starts, without disclosing why.
 *
 * Under CLI it exits non-zero, so a provisioning script chaining commands
 * with && does not treat a failed startup as success. exit("message") would
 * print the text but still report status 0.
 */
function fail_startup(string $publicMessage): never
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $publicMessage . "\n");
        exit(1);
    }

    http_response_code(500);
    exit($publicMessage);
}

try {
    $conn = new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database']
    );
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // Log the real reason, show the visitor nothing that describes the server.
    error_log('Database connection failed: ' . $e->getMessage());
    fail_startup('Database connection failed. Please try again later.');
}
