<?php
/**
 * Student listing.
 *
 * The full table is rendered server-side; assets/js/app.js then takes over
 * searching, sorting and paging through api/students.php. With JavaScript off
 * the same links and the search form still work.
 */

declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/students.php";
require_once __DIR__ . "/includes/layout.php";

require_login();

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
    error_log('Student listing failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not load the student list. Please try again later.');
}

/** Header cells, in display order. */
$columns = [
    'id'         => 'ID',
    'first_name' => 'First name',
    'last_name'  => 'Last name',
    'email'      => 'Email',
    'telephone'  => 'Telephone',
];

$listUrl = static fn(array $overrides): string => url_with('table.php', array_merge([
    'search' => $search,
    'sort'   => $sort,
    'order'  => $order,
    'page'   => $page > 1 ? $page : null,
], $overrides));

render_header('Students', 'students');
?>

<div class="page-head">
    <div>
        <h1>Students</h1>
        <p>
            <?php echo $totalRows === 0
                ? 'Nobody is registered yet.'
                : e(number_format($totalRows)) . ' student' . ($totalRows === 1 ? '' : 's') . ' on record.'; ?>
        </p>
    </div>
    <a class="button" href="form.php">Register a student</a>
</div>

<section class="card"
         data-students
         data-endpoint="api/list-students.php"
         data-csrf="<?php echo e(csrf_token()); ?>"
         data-state-sort="<?php echo e($sort); ?>"
         data-state-order="<?php echo e($order); ?>"
         data-state-page="<?php echo (int)$page; ?>">

    <div class="card__header">
        <div class="toolbar grow">
            <!-- Submitting normally is the no-JavaScript path; app.js intercepts
                 it and searches as you type instead. -->
            <form method="get" action="table.php" class="search" role="search">
                <span class="search__icon" aria-hidden="true">&#9906;</span>
                <label class="visually-hidden" for="search">Search students</label>
                <input class="input" type="search" id="search" name="search" data-search
                       placeholder="Search name, email or telephone"
                       value="<?php echo e($search); ?>" autocomplete="off">
                <span class="search__spinner" aria-hidden="true"></span>
                <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
                <input type="hidden" name="order" value="<?php echo e($order); ?>">
                <noscript><button class="button button--sm" type="submit">Search</button></noscript>
            </form>
            <a class="button button--ghost" data-export
               href="<?php echo e(url_with('export.php', ['search' => $search])); ?>">Download CSV</a>
        </div>
    </div>

    <p class="visually-hidden" role="status" aria-live="polite" data-live></p>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col" class="col-num">#</th>
                    <?php foreach ($columns as $column => $label):
                        $isActive = $sort === $column;
                        $next     = $isActive && $order === 'ASC' ? 'DESC' : 'ASC';
                    ?>
                    <th scope="col" data-column="<?php echo e($column); ?>"
                        <?php if ($isActive): ?>aria-sort="<?php echo $order === 'ASC' ? 'ascending' : 'descending'; ?>"<?php endif; ?>>
                        <a class="table__sort" data-sort
                           href="<?php echo e($listUrl(['sort' => $column, 'order' => $next, 'page' => null])); ?>">
                            <?php echo e($label); ?>
                            <span class="arrow" aria-hidden="true"><?php
                                echo $isActive ? ($order === 'ASC' ? '&#9650;' : '&#9660;') : '';
                            ?></span>
                        </a>
                    </th>
                    <?php endforeach; ?>
                    <th scope="col">Actions</th>
                </tr>
            </thead>

            <tbody data-rows>
                <?php if (!$students): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty">
                            <strong><?php echo $search !== '' ? 'No students match that search' : 'No students yet'; ?></strong>
                            <span><?php echo $search !== ''
                                ? 'Try a different name, email or phone number.'
                                : 'Register the first student to see them listed here.'; ?></span>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                <?php $rowNumber = $offset + 1; foreach ($students as $student): ?>
                <tr>
                    <td class="col-num"><?php echo $rowNumber++; ?></td>
                    <td data-label="ID"><?php echo e($student['id']); ?></td>
                    <td data-label="First name"><?php echo e($student['first_name']); ?></td>
                    <td data-label="Last name"><?php echo e($student['last_name']); ?></td>
                    <td data-label="Email"><?php echo e($student['email']); ?></td>
                    <td data-label="Telephone"><?php echo e($student['telephone']); ?></td>
                    <td data-label="Actions">
                        <div class="cell-actions">
                            <a class="link-button" href="update.php?id=<?php echo (int)$student['id']; ?>">Edit</a>
                            <!-- data-confirm routes through the accessible dialog in
                                 layout.php; without JavaScript the form just submits. -->
                            <form action="delete.php" method="post" class="inline-form"
                                  data-confirm="Delete <?php echo e($student['first_name'] . ' ' . $student['last_name']); ?>? This cannot be undone."
                                  data-confirm-title="Delete student"
                                  data-confirm-accept="Delete">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo (int)$student['id']; ?>">
                                <button type="submit" class="link-button link-button--danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager__summary" data-summary>
            <?php echo $totalRows === 0
                ? 'No students'
                : 'Showing ' . e((string)($offset + 1)) . '&ndash;' . e((string)($offset + count($students)))
                  . ' of ' . e(number_format($totalRows)); ?>
        </span>

        <nav class="pager__pages" data-pages aria-label="Pagination">
            <?php if ($page > 1): ?>
                <a class="pager__page" rel="prev" data-page="<?php echo $page - 1; ?>"
                   href="<?php echo e($listUrl(['page' => $page - 1 > 1 ? $page - 1 : null])); ?>">Previous</a>
            <?php endif; ?>

            <?php foreach (pagination_window($page, $totalPages) as $p): ?>
                <?php if ($p === null): ?>
                    <span class="pager__gap">&hellip;</span>
                <?php else: ?>
                    <a class="pager__page" data-page="<?php echo $p; ?>"
                       href="<?php echo e($listUrl(['page' => $p > 1 ? $p : null])); ?>"
                       <?php echo $p === $page ? 'aria-current="page"' : ''; ?>><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($page < $totalPages): ?>
                <a class="pager__page" rel="next" data-page="<?php echo $page + 1; ?>"
                   href="<?php echo e($listUrl(['page' => $page + 1])); ?>">Next</a>
            <?php endif; ?>
        </nav>
    </div>
</section>

<?php render_footer(); ?>
