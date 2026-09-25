<?php
/**
 * JSON listing endpoint.
 *
 * assets/js/app.js calls this so searching, sorting and paging update the table
 * without a full page load. table.php still renders the same data server-side,
 * so the page works with JavaScript disabled and this is only an enhancement.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/students.php';

require_login_json();

header('Content-Type: application/json; charset=utf-8');
// The response is per-admin session data; it must not be cached by a proxy.
header('Cache-Control: no-store');
// Belt and braces against a JSON response being interpreted as something else.
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Method not allowed.']);
    exit();
}

$search = trim((string)($_GET['search'] ?? ''));
$sort   = student_sort_column($_GET['sort'] ?? null);
$order  = student_sort_direction($_GET['order'] ?? null);
$page   = max(1, (int)($_GET['page'] ?? 1));

try {
    $totalRows  = count_students($conn, $search);
    $totalPages = (int)ceil($totalRows / STUDENTS_PER_PAGE);

    // An empty result still has a page 1; without this, ?search=zzz&page=5
    // reports offset 60 and renders a Previous link under an empty table.
    $page = $totalPages > 0 ? min($page, $totalPages) : 1;

    $offset   = ($page - 1) * STUDENTS_PER_PAGE;
    $students = find_students($conn, $search, $sort, $order, STUDENTS_PER_PAGE, $offset);
    $conn->close();
} catch (mysqli_sql_exception $e) {
    error_log('Student API query failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not load students.']);
    exit();
}

echo json_encode([
    'data' => array_map(static fn(array $s): array => [
        'id'         => (int)$s['id'],
        'first_name' => $s['first_name'],
        'last_name'  => $s['last_name'],
        'email'      => $s['email'],
        'telephone'  => $s['telephone'],
    ], $students),
    'meta' => [
        'page'        => $page,
        'per_page'    => STUDENTS_PER_PAGE,
        'total_rows'  => $totalRows,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'search'      => $search,
        'sort'        => $sort,
        'order'       => $order,
        // Page numbers the client should render, null marking a gap.
        'window'      => pagination_window($page, $totalPages),
    ],
], JSON_UNESCAPED_UNICODE);
