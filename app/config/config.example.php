<?php
/**
 * Copy this file to config/config.php and fill in your real values.
 * config/config.php is git-ignored and must NEVER be committed.
 */
return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'feebook',
        'user'     => 'feebook',
        'password' => 'change-me',
    ],
    // Public base URL, used on printed receipts for the verification link.
    'base_url' => 'http://localhost/Student_registration/app/public',
    // Set to false on a live server so errors are logged, not displayed.
    'debug'    => true,
];
