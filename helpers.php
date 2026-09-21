<?php
/**
 * Small shared view helpers.
 */

declare(strict_types=1);

/**
 * Escape a value for safe output inside HTML (prevents XSS).
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
