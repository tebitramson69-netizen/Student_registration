<?php
/**
 * Register a student.
 */

declare(strict_types=1);

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/students.php";
require_once __DIR__ . "/includes/validation.php";
require_once __DIR__ . "/includes/layout.php";

require_login();

$errors  = [];
$student = ['first_name' => '', 'last_name' => '', 'email' => '', 'telephone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $student = normalise_student($_POST);
    $errors  = validate_student($student);

    if (!$errors) {
        try {
            create_student($conn, $student);

            flash('success', $student['first_name'] . ' ' . $student['last_name'] . ' was registered.');

            // Post/Redirect/Get: a browser refresh cannot insert a second time.
            header('Location: table.php');
            exit();
        } catch (DuplicateEmailException $e) {
            $errors['email'] = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            error_log('Student insert failed: ' . $e->getMessage());
            $errors['_'] = 'Could not save the student. Please try again.';
        }
    }
}

render_header('Register a student', 'register');
?>

<div class="page-head">
    <div>
        <h1>Register a student</h1>
        <p>All four details are required.</p>
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
        <!-- data-validate turns on the matching client-side checks in app.js.
             validation.php runs the same rules again on submit. -->
        <form action="form.php" method="post" data-validate novalidate>
            <?php echo csrf_field(); ?>

            <div class="grid-2">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input class="input" type="text" id="first_name" name="first_name"
                           value="<?php echo e($student['first_name']); ?>"
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
                           value="<?php echo e($student['last_name']); ?>"
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
                       value="<?php echo e($student['email']); ?>"
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
                       value="<?php echo e($student['telephone']); ?>"
                       maxlength="<?php echo TELEPHONE_MAX_LENGTH; ?>"
                       autocomplete="tel" placeholder="+237 6XX XX XX XX"
                       <?php echo isset($errors['telephone']) ? 'aria-invalid="true"' : ''; ?>
                       aria-describedby="telephone_error telephone_hint">
                <span class="field__error" id="telephone_error" data-error-for="telephone"><?php
                    echo e($errors['telephone'] ?? '');
                ?></span>
                <span class="field__hint" id="telephone_hint">Local or international format both work.</span>
            </div>

            <div class="form-actions">
                <button class="button" type="submit">Register student</button>
                <a class="button button--ghost" href="table.php">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php render_footer(); ?>
