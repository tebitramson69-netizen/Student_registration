<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/payments.php';

session_start_secure();

if (!config()['debug']) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}
