<?php
/**
 * Edit a student.
 */

declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/students.php";
require_once __DIR__ . "/includes/validation.php";
require_once __DIR__ . "/includes/layout.php";

require_login();

$errors  = [];
$student = null;
$id      = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $id = (int)($_POST['id'] ?? 0);

    // Keep what was submitted so the form can be redisplayed on error.
    $student = normalise_student($_POST);
    $errors  = validate_student($student);

    try {
        // Confirm the row is still there. affected_rows cannot stand in for
        // this: MySQL reports 0 both for a missing row and for a row saved
        // with values identical to the ones it already held.
        if ($id <= 0 || !find_student($conn, $id)) {
            flash('error', 'That student no longer exists.');
            header('Location: table.php');
            exit();
        }

        if (!$errors) {
            update_student($conn, $id, $student);

            flash('success', $student['first_name'] . ' ' . $student['last_name'] . ' was updated.');
            header('Location: table.php');
            exit();
        }
    } catch (DuplicateEmailException $e) {
        $errors['email'] = $e->getMessage();
    } catch (mysqli_sql_exception $e) {
        error_log('Student update failed: ' . $e->getMessage());
        $errors['_'] = 'Could not save the changes. Please try again.';
    }

    $student['id'] = $id;
} else {
    $id = (int)($_GET['id'] ?? 0);

    try {
        $student = $id > 0 ? find_student($conn, $id) : null;
    } catch (mysqli_sql_exception $e) {
        // Its own catch: reporting a failed read as "could not save" would be
        // a lie, and would render an empty form as though it were the student.
        error_log('Student lookup failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Could not load the student. Please try again later.');
    }

    // No id, or no such student: nothing to edit, so go back to the list
    // rather than render a form over undefined values.
    if (!$student) {
        flash('error', 'That student could not be found.');
        header('Location: table.php');
        exit();
    }
}

$fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));

render_header('Edit student', 'students');
?>

<div class="page-head">
    <div>
        <h1>Edit student</h1>
        <p><?php echo $fullName !== '' ? e($fullName) : 'Student #' . (int)$id; ?></p>
    </div>
    <a href="table.php">Back to students</a>
</div>

<?php if ($errors): ?>
    <div class="flash flash--error mb-5" role="alert">
        <span class="flash__icon" aria-hidden="true">&#9888;</span>
        <span>
            <strong>Error.</strong>
            <?php echo e($errors['_'] ?? 'Please correct the fields marked below.'); ?>
        </span>
    </div>
<?php endif; ?>

<section class="card card--form">
    <div class="card__body">
        <form action="update.php" method="post" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo (int)($student['id'] ?? $id); ?>">

            <div class="grid-2">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input class="input" type="text" id="first_name" name="first_name"
                           value="<?php echo e($student['first_name'] ?? ''); ?>"
                           maxlength="<?php echo NAME_MAX_LENGTH; ?>"
                           autocomplete="given-name" autofocus
                           <?php echo isset($errors['first_name']) ? 'aria-invalid="true"' : ''; ?>
                           aria-describedby="first_name_error">
                    <span class="field__error" id="first_name_error" data-error-for="first_name"><?php
                        echo e($errors['first_name'] ?? '');
                    ?></span>
                </div>

                <div class="field">
                    <label for="last_name">Last name</label>
                    <input class="input" type="text" id="last_name" name="last_name"
                           value="<?php echo e($student['last_name'] ?? ''); ?>"
                           maxlength="<?php echo NAME_MAX_LENGTH; ?>"
                           autocomplete="family-name"
                           <?php echo isset($errors['last_name']) ? 'aria-invalid="true"' : ''; ?>
                           aria-describedby="last_name_error">
                    <span class="field__error" id="last_name_error" data-error-for="last_name"><?php
                        echo e($errors['last_name'] ?? '');
                    ?></span>
                </div>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input class="input" type="email" id="email" name="email"
                       value="<?php echo e($student['email'] ?? ''); ?>"
                       maxlength="<?php echo EMAIL_MAX_LENGTH; ?>"
                       autocomplete="email"
                       <?php echo isset($errors['email']) ? 'aria-invalid="true"' : ''; ?>
                       aria-describedby="email_error">
                <span class="field__error" id="email_error" data-error-for="email"><?php
                    echo e($errors['email'] ?? '');
                ?></span>
            </div>

            <div class="field">
                <label for="telephone">Telephone</label>
                <input class="input" type="tel" id="telephone" name="telephone"
                       value="<?php echo e($student['telephone'] ?? ''); ?>"
                       maxlength="<?php echo TELEPHONE_MAX_LENGTH; ?>"
                       autocomplete="tel" placeholder="+237 6XX XX XX XX"
                       <?php echo isset($errors['telephone']) ? 'aria-invalid="true"' : ''; ?>
                       aria-describedby="telephone_error">
                <span class="field__error" id="telephone_error" data-error-for="telephone"><?php
                    echo e($errors['telephone'] ?? '');
                ?></span>
            </div>

            <div class="form-actions">
                <button class="button" type="submit">Save changes</button>
                <a class="button button--ghost" href="table.php">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php render_footer(); ?>
