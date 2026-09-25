<?php
/**
 * Dashboard. The landing page after signing in.
 */

declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/students.php";
require_once __DIR__ . "/includes/layout.php";

require_login();

try {
    $stats     = student_stats($conn);
    $recent    = find_recent_students($conn, 5);
    $hasDates  = students_have_created_at($conn);
    $conn->close();
} catch (mysqli_sql_exception $e) {
    error_log('Dashboard query failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Could not load the dashboard. Please try again later.');
}

render_header('Dashboard', 'dashboard');
?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>An overview of the student register.</p>
    </div>
    <a class="button" href="form.php">Register a student</a>
</div>

<?php if (!$hasDates): ?>
    <div class="flash flash--info mb-5" role="status">
        <span class="flash__icon" aria-hidden="true">&#8505;</span>
        <span>
            <strong>Note.</strong>
            Registration dates are unavailable because this database predates the
            <code>created_at</code> column. Run
            <code>migrations/001_add_student_metadata.sql</code> to enable the
            figures below.
        </span>
    </div>
<?php endif; ?>

<!-- One hero figure per view; the rest are stat tiles. -->
<div class="stats">
    <div class="stat stat--hero">
        <p class="stat__label">Students registered</p>
        <p class="stat__value"><?php echo e(compact_number($stats['total'])); ?></p>
        <p class="stat__note">Total on record</p>
    </div>

    <div class="stat">
        <p class="stat__label">Added today</p>
        <p class="stat__value"><?php echo $stats['today'] === null ? '&mdash;' : e(compact_number($stats['today'])); ?></p>
        <p class="stat__note">Since midnight</p>
    </div>

    <div class="stat">
        <p class="stat__label">Added this week</p>
        <p class="stat__value"><?php echo $stats['week'] === null ? '&mdash;' : e(compact_number($stats['week'])); ?></p>
        <p class="stat__note">Last 7 days</p>
    </div>

    <div class="stat">
        <p class="stat__label">Added this month</p>
        <p class="stat__value"><?php echo $stats['month'] === null ? '&mdash;' : e(compact_number($stats['month'])); ?></p>
        <p class="stat__note">Last 30 days</p>
    </div>
</div>

<section class="card">
    <div class="card__header">
        <h2>Recently added</h2>
        <a href="table.php">View all students</a>
    </div>

    <?php if (!$recent): ?>
        <div class="empty">
            <strong>No students yet</strong>
            <span>Register the first student to see them listed here.</span>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Telephone</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $student): ?>
                <tr>
                    <td data-label="Name">
                        <?php echo e($student['first_name'] . ' ' . $student['last_name']); ?>
                    </td>
                    <td data-label="Email"><?php echo e($student['email']); ?></td>
                    <td data-label="Telephone"><?php echo e($student['telephone']); ?></td>
                    <td data-label="Actions">
                        <a href="update.php?id=<?php echo (int)$student['id']; ?>">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php render_footer(); ?>
