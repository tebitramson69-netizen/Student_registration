<?php
/**
 * Shared page chrome.
 *
 * Every page called render_header() / render_footer() instead of repeating the
 * doctype, the nav and its own <style> block.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * @param string $title    shown in the tab and as the page heading
 * @param string $active   nav item to mark as current: dashboard|students|register
 * @param bool   $chromeless  true for the sign-in page, which has no nav
 */
function render_header(string $title, string $active = '', bool $chromeless = false): void
{
    $flashes = take_flashes();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?php echo e($title); ?> &middot; Student Registry</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body<?php echo $chromeless ? ' class="page--centered"' : ''; ?>>
    <a class="skip-link" href="#main">Skip to main content</a>

<?php if (!$chromeless): ?>
    <header class="appbar">
        <div class="appbar__inner">
            <a class="brand" href="index.php">
                <span class="brand__mark" aria-hidden="true">SR</span>
                <span>Student Registry</span>
            </a>

            <nav class="nav" aria-label="Main">
                <a href="index.php"<?php echo $active === 'dashboard' ? ' aria-current="page"' : ''; ?>>Dashboard</a>
                <a href="table.php"<?php echo $active === 'students' ? ' aria-current="page"' : ''; ?>>Students</a>
                <a href="form.php"<?php echo $active === 'register' ? ' aria-current="page"' : ''; ?>>Register</a>
            </nav>

            <div class="appbar__account">
                <button type="button" class="icon-button" data-theme-toggle
                        aria-label="Switch between light and dark theme">
                    <span aria-hidden="true" data-theme-icon>&#9789;</span>
                </button>
                <span class="appbar__user">
                    <?php echo e(current_admin_username()); ?>
                </span>
                <form action="logout.php" method="post" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="button button--ghost">Sign out</button>
                </form>
            </div>
        </div>
    </header>
<?php endif; ?>

    <main id="main" class="<?php echo $chromeless ? 'shell shell--narrow' : 'shell'; ?>">
        <?php if ($flashes): ?>
        <div class="flashes" data-flashes>
            <?php foreach ($flashes as $flash):
                $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
                // The icon and the word carry the meaning as well as the colour,
                // so the state is not signalled by colour alone.
                $icon  = ['success' => '&#10003;', 'error' => '&#9888;', 'info' => '&#8505;'][$type];
                $label = ['success' => 'Success', 'error' => 'Error', 'info' => 'Note'][$type];
            ?>
                <div class="flash flash--<?php echo $type; ?>" role="<?php echo $type === 'error' ? 'alert' : 'status'; ?>">
                    <span class="flash__icon" aria-hidden="true"><?php echo $icon; ?></span>
                    <span><strong><?php echo $label; ?>.</strong> <?php echo e($flash['message']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
<?php
}

function render_footer(): void
{
    ?>
    </main>

    <!-- Confirmation dialog, driven by assets/js/app.js. Any form carrying
         data-confirm routes through it instead of window.confirm(). -->
    <dialog class="dialog" data-confirm-dialog>
        <h2 class="dialog__title" data-confirm-title>Are you sure?</h2>
        <p class="dialog__body" data-confirm-body></p>
        <div class="dialog__actions">
            <button type="button" class="button button--ghost" data-confirm-cancel>Cancel</button>
            <button type="button" class="button button--danger" data-confirm-accept>Delete</button>
        </div>
    </dialog>

    <div class="toasts" data-toasts aria-live="polite" aria-atomic="false"></div>

    <script src="assets/js/app.js" defer></script>
</body>
</html>
<?php
}
