<?php
declare(strict_types=1);

/** Escape for HTML output. Use on EVERY value printed into a page. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format whole FCFA for display, e.g. 150000 -> "150 000 FCFA". */
function money(int $fcfa): string
{
    return number_format($fcfa, 0, ',', ' ') . ' FCFA';
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** Read a positive integer from input, or null when absent/invalid. */
function int_param(array $source, string $key): ?int
{
    if (!isset($source[$key]) || !is_string($source[$key]) || !ctype_digit($source[$key])) {
        return null;
    }
    $value = (int)$source[$key];
    return $value > 0 ? $value : null;
}

function flash(string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $existing = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $existing;
}

/** Whitelist a sort column. Never interpolate raw user input into ORDER BY. */
function sort_column(array $allowed, string $default): string
{
    $requested = $_GET['sort'] ?? '';
    return in_array($requested, $allowed, true) ? $requested : $default;
}

function sort_direction(): string
{
    return (($_GET['order'] ?? '') === 'DESC') ? 'DESC' : 'ASC';
}
