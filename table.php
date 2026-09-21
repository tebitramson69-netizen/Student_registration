<?php
declare(strict_types=1);

require_once "connection.php";
require_once "helpers.php";

// Number of records per page
$limit = 15;

// Current page number, never below 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Sorting. Column and direction are table/keyword identifiers, which a prepared
// statement cannot bind, so they are validated against a whitelist instead.
$validColumns = ['id', 'first_name', 'last_name', 'email', 'telephone'];
$sort  = isset($_GET['sort']) && in_array($_GET['sort'], $validColumns, true) ? $_GET['sort'] : 'first_name';
$order = isset($_GET['order']) && $_GET['order'] === 'DESC' ? 'DESC' : 'ASC';

$search = trim((string)($_GET['search'] ?? ''));

$where  = '';
$params = [];
$types  = '';

if ($search !== '') {
    $like  = '%' . $search . '%';
    $where = "WHERE first_name LIKE ?
                 OR last_name  LIKE ?
                 OR email      LIKE ?
                 OR telephone  LIKE ?";
    $params = [$like, $like, $like, $like];
    $types  = 'ssss';
}

try {
    // Total matching records first, so pagination reflects the search results
    // rather than the whole table.
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM students $where");
    if ($params) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $totalPages = (int)ceil($totalRows / $limit);
    if ($totalPages > 0 && $page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, email, telephone
         FROM students
         $where
         ORDER BY $sort $order
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $conn->close();
} catch (mysqli_sql_exception $e) {
    // An uncaught exception would print the SQL and absolute paths, which a
    // default XAMPP install (display_errors=On) shows to the visitor.
    error_log('Student listing failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not load the student list. Please try again later.');
}

/**
 * Build a column header link that toggles the sort direction.
 */
function sortLink(string $column, string $label, string $currentSort, string $currentOrder, string $search, int $page): string
{
    $order = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $query = ['sort' => $column, 'order' => $order, 'page' => $page];
    if ($search !== '') {
        $query['search'] = $search;
    }

    return '<a href="table.php?' . e(http_build_query($query)) . '">' . e($label) . '</a>';
}

/**
 * Build a pagination link that preserves the current search and sorting.
 */
function pageLink(int $targetPage, string $search, string $sort, string $order): string
{
    $query = ['page' => $targetPage, 'sort' => $sort, 'order' => $order];
    if ($search !== '') {
        $query['search'] = $search;
    }

    return 'table.php?' . e(http_build_query($query));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Table</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .search-box { margin-bottom: 15px; }
        .pagination { margin-top: 15px; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 5px 10px; border: 1px solid #ccc; }
        .pagination a.active { background-color: #007BFF; color: white; }
    </style>
</head>
<body>
    <h2>Registered Students (Alphabetical Order)</h2>

    <!-- Search Form -->
    <div class="search-box">
        <form method="get" action="table.php">
            <input type="text" name="search" placeholder="Search by name, email, or phone"
                   value="<?php echo e($search); ?>">
            <input type="submit" value="Search">
        </form>
    </div>

    <table>
        <tr>
            <th>#</th>
            <th><?php echo sortLink('id', 'ID', $sort, $order, $search, $page); ?></th>
            <th><?php echo sortLink('first_name', 'First Name', $sort, $order, $search, $page); ?></th>
            <th><?php echo sortLink('last_name', 'Last Name', $sort, $order, $search, $page); ?></th>
            <th><?php echo sortLink('email', 'Email', $sort, $order, $search, $page); ?></th>
            <th><?php echo sortLink('telephone', 'Telephone', $sort, $order, $search, $page); ?></th>
            <th>Actions</th>
        </tr>
        <?php if (!$users): ?>
        <tr><td colspan="7">No students found.</td></tr>
        <?php endif; ?>
        <?php
        $rowNumber = $offset + 1;
        foreach ($users as $user): ?>
        <tr>
            <td><?php echo $rowNumber++; ?></td>
            <td><?php echo e($user['id']); ?></td>
            <td><?php echo e($user['first_name']); ?></td>
            <td><?php echo e($user['last_name']); ?></td>
            <td><?php echo e($user['email']); ?></td>
            <td><?php echo e($user['telephone']); ?></td>
            <td>
                <a href="update.php?id=<?php echo (int)$user['id']; ?>">Update</a> |
                <a href="delete.php?id=<?php echo (int)$user['id']; ?>"
                   onclick="return confirm('Are you sure you want to delete this student?');">
                   Delete
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Pagination Controls -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?php echo pageLink($page - 1, $search, $sort, $order); ?>">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?php echo pageLink($i, $search, $sort, $order); ?>"
               class="<?php echo ($i === $page) ? 'active' : ''; ?>">
               <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?php echo pageLink($page + 1, $search, $sort, $order); ?>">Next</a>
        <?php endif; ?>
    </div>

<button type="button" onclick="location.href='form.php';">Register another student</button>
<br><br>
<button type="button" onclick="location.href='export.php';">Download CSV</button>
</body>
</html>
