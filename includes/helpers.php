<?php
/**
 * Shared view and formatting helpers.
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

/**
 * Build a URL for the current page with some query parameters replaced.
 * Parameters set to null are dropped.
 */
function url_with(string $path, array $params): string
{
    $params = array_filter($params, static fn($v) => $v !== null && $v !== '');

    return $params === [] ? $path : $path . '?' . http_build_query($params);
}

/**
 * Which page numbers a pager should show.
 *
 * Returns page numbers, with null marking a gap. The previous version printed
 * every page, so 5,000 students produced 334 links.
 *
 * @return array<int|null>
 */
function pagination_window(int $page, int $totalPages, int $span = 2): array
{
    if ($totalPages < 1) {
        return [];
    }

    $pages = [1, $totalPages];
    for ($i = $page - $span; $i <= $page + $span; $i++) {
        $pages[] = $i;
    }

    $pages = array_values(array_unique(array_filter(
        $pages,
        static fn(int $p): bool => $p >= 1 && $p <= $totalPages
    )));
    sort($pages);

    $window  = [];
    $previous = 0;
    foreach ($pages as $p) {
        if ($previous !== 0 && $p > $previous + 1) {
            $window[] = null; // gap
        }
        $window[]  = $p;
        $previous  = $p;
    }

    return $window;
}

/**
 * Compact a count for display in a stat tile: 1284 -> "1,284", 12900 -> "12.9K".
 */
function compact_number(int $value): string
{
    $trim = static fn(float $n): string => rtrim(rtrim(number_format($n, 1), '0'), '.');

    if ($value < 10000) {
        return number_format($value);
    }

    // The bound is 999,500 rather than 1,000,000 because 999,999 / 1000 rounds
    // to 1000.0, which would print as the nonsense "1,000K".
    if ($value < 999500) {
        return $trim($value / 1000) . 'K';
    }

    return $trim($value / 1000000) . 'M';
}

/**
 * A cell starting with =, +, - or @ is treated as a formula by Excel and
 * LibreOffice when the CSV is opened, so a student named "=cmd|..." would run
 * as one. Prefixing a single quote keeps the cell as text.
 */
function csv_safe(string|int|float|null $value): string
{
    $value = (string)$value;

    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }

    return $value;
}

/* ---------------------------------------------------------------------------
 * Flash messages
 *
 * A redirect after a successful POST cannot carry a message in the response,
 * so it is parked in the session and shown on the next page.
 * ------------------------------------------------------------------------ */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Read and clear the queued messages.
 *
 * @return list<array{type: string, message: string}>
 */
function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return is_array($flashes) ? $flashes : [];
}
