<?php
/**
 * Small shared view helpers.
 */

declare(strict_types=1);

/**
 * Escape a value for safe output inside HTML (prevents XSS).
 *
 * Accepts any scalar, not just strings: a prepared statement's get_result()
 * returns INT and DECIMAL columns as native PHP int/float, and every page
 * here declares strict_types, so a string-only signature would fatal on the
 * first row rendered.
 */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
