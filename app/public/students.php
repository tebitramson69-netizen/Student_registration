<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$institutionId = current_institution_id();

$perPage = 15;
$page    = int_param($_GET, 'page') ?? 1;
$offset  = ($page - 1) * $perPage;

$sort  = sort_column(['student_code', 'first_name', 'last_name', 'phone', 'created_at'], 'last_name');
$order = sort_direction();

$search = trim((string)($_GET['search'] ?? ''));

// One WHERE clause, used by BOTH the page query and the count query, so the
// page numbers stay correct while a search filter is active.
$where  = 'WHERE s.institution_id = ?';
$params = [$institutionId];
if ($search !== '') {
    $where .= ' AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.phone LIKE ?
                     OR s.email LIKE ? OR s.student_code LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM students s $where");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalRows / $perPage);

// $sort and $order come from the whitelists above, never from raw input.
$stmt = db()->prepare(
    "SELECT s.id, s.student_code, s.first_name, s.last_name, s.phone,
            COUNT(DISTINCT e.id) AS enrolments
       FROM students s
       LEFT JOIN enrolments e ON e.student_id = s.id
       $where
      GROUP BY s.id
      ORDER BY s.$sort $order
      LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$students = $stmt->fetchAll();

page_header('Students');
?>
<h1>Students <span class="muted">(<?= $totalRows ?>)</span></h1>

<div class="toolbar">
    <form method="get">
        <input type="text" name="search" placeholder="Search name, phone, email or code"
               value="<?= e($search) ?>">
        <button class="btn" type="submit">Search</button>
        <?php if ($search !== ''): ?><a class="btn secondary" href="students.php">Clear</a><?php endif; ?>
    </form>
    <a class="btn" href="student_form.php">Register a student</a>
</div>

<?php if (!$students): ?>
    <p class="muted">No students found.</p>
<?php else: ?>
<div class="table-scroll">
<table>
    <tr>
        <th><?= sort_header('student_code', 'Code', $sort, $order) ?></th>
        <th><?= sort_header('last_name', 'Last name', $sort, $order) ?></th>
        <th><?= sort_header('first_name', 'First name', $sort, $order) ?></th>
        <th><?= sort_header('phone', 'Phone', $sort, $order) ?></th>
        <th class="num">Enrolments</th>
        <th></th>
    </tr>
    <?php foreach ($students as $s): ?>
    <tr>
        <td><?= e($s['student_code']) ?></td>
        <td><?= e($s['last_name']) ?></td>
        <td><?= e($s['first_name']) ?></td>
        <td><?= e($s['phone']) ?></td>
        <td class="num"><?= (int)$s['enrolments'] ?></td>
        <td><a href="student_view.php?id=<?= (int)$s['id'] ?>">Open</a></td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php pagination($page, $totalPages); ?>
<?php endif; ?>
<?php page_footer(); ?>
