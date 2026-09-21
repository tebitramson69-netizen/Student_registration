<?php
/**
 * Database connection.
 *
 * Credentials are read from config.php when that file exists. config.php is
 * git-ignored, so real credentials never enter version control. When it is
 * absent the local XAMPP defaults below are used, which keeps a fresh clone
 * working out of the box on a development machine.
 *
 * Copy config.example.php to config.php and edit it before deploying.
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
    $config = array_merge($config, require __DIR__ . '/config.php');
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
    http_response_code(500);
    exit('Database connection failed. Please try again later.');
}
