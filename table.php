<?php
include "connection.php";

// Number of records per page
$limit = 15;

// Get current page number from URL, default to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the starting record
$offset = ($page - 1) * $limit;

// Sorting
$validColumns = ['id','first_name','last_name','email','telephone'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $validColumns) ? $_GET['sort'] : 'first_name';
$order = isset($_GET['order']) && $_GET['order'] === 'DESC' ? 'DESC' : 'ASC';

// Default query
$sql = "SELECT * FROM students ORDER BY $sort $order LIMIT $limit OFFSET $offset";

// If search submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $sql = "SELECT * FROM students 
            WHERE first_name LIKE '%$search%' 
               OR last_name LIKE '%$search%' 
               OR email LIKE '%$search%' 
               OR telephone LIKE '%$search%'
            ORDER BY $sort $order
            LIMIT $limit OFFSET $offset";
}

// Run query
$result = mysqli_query($conn, $sql);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Count total records for pagination
$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students");
$totalRows = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRows / $limit);

mysqli_free_result($result);
mysqli_close($conn);

// Helper to toggle sort order
function sortLink($column, $label, $currentSort, $currentOrder, $search, $page) {
    $order = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $url = "table.php?sort=$column&order=$order&page=$page";
    if (!empty($search)) {
        $url .= "&search=" . urlencode($search);
    }
    return "<a href=\"$url\">$label</a>";
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
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <input type="submit" value="Search">
        </form>
    </div>

    <table>
        <tr>
            <th>#</th>
            <th><?php echo sortLink('id','ID',$sort,$order,$_GET['search'] ?? '',$page); ?></th>
            <th><?php echo sortLink('first_name','First Name',$sort,$order,$_GET['search'] ?? '',$page); ?></th>
            <th><?php echo sortLink('last_name','Last Name',$sort,$order,$_GET['search'] ?? '',$page); ?></th>
            <th><?php echo sortLink('email','Email',$sort,$order,$_GET['search'] ?? '',$page); ?></th>
            <th><?php echo sortLink('telephone','Telephone',$sort,$order,$_GET['search'] ?? '',$page); ?></th>
            <th>Actions</th>
        </tr>
        <?php 
        $rowNumber = $offset + 1; 
        foreach ($users as $user): ?>
        <tr>
            <td><?php echo $rowNumber++; ?></td>
            <td><?php echo htmlspecialchars($user['id']); ?></td>
            <td><?php echo htmlspecialchars($user['first_name']); ?></td>
            <td><?php echo htmlspecialchars($user['last_name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['telephone']); ?></td>
            <td>
                <a href="update.php?id=<?php echo $user['id']; ?>">Update</a> | 
                <a href="delete.php?id=<?php echo $user['id']; ?>" 
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
            <a href="table.php?page=<?php echo $page-1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo "&sort=$sort&order=$order"; ?>">Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="table.php?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo "&sort=$sort&order=$order"; ?>" 
               class="<?php echo ($i == $page) ? 'active' : ''; ?>">
               <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="table.php?page=<?php echo $page+1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo "&sort=$sort&order=$order"; ?>">Next</a>
        <?php endif; ?>
    </div>
<button color="blue"><a href="form.php">Register another student</a> </button>
<br><br>
<button color="blue"><a href="export.php">Download CSV</a></button>
</body>
</html>
