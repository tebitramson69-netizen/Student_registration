<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_layout.php';

$user = require_login();
$institutionId = $user['institution_id'];

$id      = int_param($_GET, 'id');
$student = ['id' => null, 'student_code' => '', 'first_name' => '', 'last_name' => '',
            'phone' => '', 'email' => '', 'gender' => ''];
$errors  = [];

if ($id !== null) {
    // Scoped by institution: a guessed id from another centre simply is not found.
    $stmt = db()->prepare('SELECT * FROM students WHERE id = ? AND institution_id = ?');
    $stmt->execute([$id, $institutionId]);
    $found = $stmt->fetch();
    if (!$found) {
        http_response_code(404);
        exit('Student not found.');
    }
    $student = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $student['student_code'] = strtoupper(trim((string)($_POST['student_code'] ?? '')));
    $student['first_name']   = trim((string)($_POST['first_name'] ?? ''));
    $student['last_name']    = trim((string)($_POST['last_name'] ?? ''));
    $student['phone']        = trim((string)($_POST['phone'] ?? ''));
    $student['email']        = trim((string)($_POST['email'] ?? ''));
    $student['gender']       = in_array($_POST['gender'] ?? '', ['F', 'M'], true) ? $_POST['gender'] : '';

    if ($student['first_name'] === '') { $errors[] = 'First name is required.'; }
    if ($student['last_name'] === '')  { $errors[] = 'Last name is required.'; }
    if ($student['phone'] === '')      { $errors[] = 'Phone number is required — it is how you reach them about fees.'; }
    if ($student['email'] !== '' && !filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'That email address is not valid.';
    }

    // Auto-generate a code when the centre does not use its own numbering.
    if ($student['student_code'] === '') {
        $next = db()->prepare('SELECT COUNT(*) FROM students WHERE institution_id = ?');
        $next->execute([$institutionId]);
        $student['student_code'] = sprintf('S%04d', (int)$next->fetchColumn() + 1);
    }

    if (!$errors) {
        try {
            if ($id === null) {
                $stmt = db()->prepare(
                    'INSERT INTO students (institution_id, student_code, first_name, last_name, phone, email, gender)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$institutionId, $student['student_code'], $student['first_name'],
                                $student['last_name'], $student['phone'], $student['email'], $student['gender']]);
                $newId = (int)db()->lastInsertId();
                flash('Student registered. Now enrol them on a programme.');
                redirect('enrol.php?student_id=' . $newId);
            }
            $stmt = db()->prepare(
                'UPDATE students SET student_code = ?, first_name = ?, last_name = ?,
                        phone = ?, email = ?, gender = ?
                  WHERE id = ? AND institution_id = ?'
            );
            $stmt->execute([$student['student_code'], $student['first_name'], $student['last_name'],
                            $student['phone'], $student['email'], $student['gender'], $id, $institutionId]);
            flash('Student updated.');
            redirect('student_view.php?id=' . $id);
        } catch (PDOException $ex) {
            $errors[] = ((int)$ex->getCode() === 23000)
                ? 'That student code is already used in this centre.'
                : 'Could not save the student.';
        }
    }
}

page_header($id === null ? 'Register a student' : 'Edit student');
?>
<h1><?= $id === null ? 'Register a student' : 'Edit student' ?></h1>

<?php if ($errors): ?>
    <div class="error"><?php foreach ($errors as $msg): ?><div><?= e($msg) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card">
<form method="post">
    <?= csrf_field() ?>
    <div class="row">
        <div>
            <label for="first_name">First name *</label>
            <input id="first_name" name="first_name" required value="<?= e($student['first_name']) ?>">
        </div>
        <div>
            <label for="last_name">Last name *</label>
            <input id="last_name" name="last_name" required value="<?= e($student['last_name']) ?>">
        </div>
    </div>
    <div class="row">
        <div>
            <label for="phone">Phone *</label>
            <input id="phone" name="phone" required value="<?= e($student['phone']) ?>" placeholder="6XX XX XX XX">
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="<?= e($student['email']) ?>">
        </div>
    </div>
    <div class="row">
        <div>
            <label for="student_code">Student code</label>
            <input id="student_code" name="student_code" value="<?= e($student['student_code']) ?>"
                   placeholder="Leave blank to generate one">
        </div>
        <div>
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                <option value=""  <?= $student['gender'] === ''  ? 'selected' : '' ?>>—</option>
                <option value="F" <?= $student['gender'] === 'F' ? 'selected' : '' ?>>Female</option>
                <option value="M" <?= $student['gender'] === 'M' ? 'selected' : '' ?>>Male</option>
            </select>
        </div>
    </div>
    <button class="btn" type="submit"><?= $id === null ? 'Register student' : 'Save changes' ?></button>
    <a class="btn secondary" href="students.php">Cancel</a>
</form>
</div>
<?php page_footer(); ?>
