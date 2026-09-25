<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";

// POST only: a plain link would let another site log the admin out through
// an <img> tag or a redirect.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: table.php");
    exit();
}

require_csrf();
logout_admin();

header("Location: login.php");
exit();
