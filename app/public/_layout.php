<?php
declare(strict_types=1);

function page_header(string $title, bool $showNav = true): void
{
    $user = current_user();
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — FeeBook</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if ($showNav && $user): ?>
<nav class="nav">
    <a class="brand" href="index.php">FeeBook</a>
    <a href="students.php">Students</a>
    <a href="programmes.php">Programmes</a>
    <a href="arrears.php">Who owes</a>
    <span class="spacer"></span>
    <span class="who"><?= e($user['full_name']) ?></span>
    <a href="logout.php">Sign out</a>
</nav>
<?php endif; ?>
<main class="wrap">
<?php if ($msg = flash()): ?>
    <p class="flash"><?= e($msg) ?></p>
<?php endif; ?>
<?php
}

function page_footer(): void
{
    ?>
</main>
</body>
</html>
<?php
}

/** Build a sortable column header link that preserves the current filters. */
function sort_header(string $column, string $label, string $currentSort, string $currentOrder): string
{
    $nextOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $query = $_GET;
    $query['sort']  = $column;
    $query['order'] = $nextOrder;
    unset($query['page']);
    $arrow = $currentSort === $column ? ($currentOrder === 'ASC' ? ' ▲' : ' ▼') : '';
    return '<a href="?' . e(http_build_query($query)) . '">' . e($label) . $arrow . '</a>';
}

/** Pagination that respects whatever search filter is active. */
function pagination(int $page, int $totalPages): void
{
    if ($totalPages < 2) {
        return;
    }
    echo '<div class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $query = $_GET;
        $query['page'] = $i;
        $class = $i === $page ? ' class="active"' : '';
        echo '<a' . $class . ' href="?' . e(http_build_query($query)) . '">' . $i . '</a>';
    }
    echo '</div>';
}
