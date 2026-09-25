<?php
/**
 * Unit tests for the logic that does not need a database.
 *
 *     php tests/run.php
 *
 * The data layer's SQL and the browser behaviour are not covered here; those
 * need a MySQL server and a browser respectively.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/students.php';

$passed = 0;
$failed = 0;

function ok(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        printf("  PASS  %s\n", $name);
    } else {
        $failed++;
        printf("  FAIL  %s%s\n", $name, $detail !== '' ? "  -> $detail" : '');
    }
}

function same(string $name, mixed $expected, mixed $actual): void
{
    ok($name, $expected === $actual, 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}

echo "\nvalidation\n";
$valid = ['first_name' => 'Ramson', 'last_name' => 'Titih', 'email' => 'r@example.cm', 'telephone' => '+237 677123456'];
same('a complete student passes', [], validate_student(normalise_student($valid)));
ok('accented names are allowed',
    validate_student(normalise_student(['first_name' => 'Étienne', 'last_name' => 'Nkoló', 'email' => 'e@x.cm', 'telephone' => '677123456'])) === []);
ok('apostrophes and hyphens are allowed',
    validate_student(normalise_student(['first_name' => "O'Brien-Ndi", 'last_name' => 'St. Jean', 'email' => 'o@x.cm', 'telephone' => '677123456'])) === []);
ok('digits in a name are rejected',
    isset(validate_student(normalise_student(['first_name' => 'Ram5on'] + $valid))['first_name']));
ok('markup in a name is rejected',
    isset(validate_student(normalise_student(['first_name' => '<script>'] + $valid))['first_name']));
ok('a malformed email is rejected',
    isset(validate_student(normalise_student(['email' => 'nope'] + $valid))['email']));
ok('a phone with too few digits is rejected',
    isset(validate_student(normalise_student(['telephone' => '12345'] + $valid))['telephone']));
ok('an over-long name is rejected',
    isset(validate_student(normalise_student(['first_name' => str_repeat('a', 101)] + $valid))['first_name']));

echo "\nnormalisation\n";
$n = normalise_student(['first_name' => '  Ramson   Paul ', 'last_name' => 'Titih', 'email' => ' R.Titih@Example.CM ', 'telephone' => ' 677 12 34 56 ']);
same('runs of whitespace collapse', 'Ramson Paul', $n['first_name']);
same('email is lower-cased so the UNIQUE index catches duplicates', 'r.titih@example.cm', $n['email']);

echo "\npagination window\n";
same('a single page', [1], pagination_window(1, 1));
same('no pages at all', [], pagination_window(1, 0));
same('gap only on the right', [1, 2, 3, null, 20], pagination_window(1, 20));
same('gaps on both sides', [1, null, 8, 9, 10, 11, 12, null, 20], pagination_window(10, 20));
same('gap only on the left', [1, null, 18, 19, 20], pagination_window(20, 20));
ok('a 100-page list never renders 100 links', count(pagination_window(50, 100)) <= 9);

echo "\ncompact numbers\n";
same('thousands keep their separator', '1,284', compact_number(1284));
same('ten thousand compacts', '12.9K', compact_number(12900));
same('just below a million does not print 1,000K', '1M', compact_number(999999));
same('millions compact', '1.5M', compact_number(1500000));

echo "\nCSV injection\n";
same('a formula is neutralised', "'=1+1", csv_safe('=1+1'));
same('a leading plus is neutralised', "'+1", csv_safe('+1'));
same('a leading minus is neutralised', "'-1", csv_safe('-1'));
same('a leading at is neutralised', "'@SUM(A1)", csv_safe('@SUM(A1)'));
same('an ordinary name is untouched', 'Ramson', csv_safe('Ramson'));
same('an integer column is untouched', '7', csv_safe(7));

echo "\nsearch filter\n";
[$where, $types, $params] = student_search_filter('');
same('an empty search adds no WHERE clause', '', $where);
same('and binds nothing', [], $params);
[$where, $types, $params] = student_search_filter('100%');
same('four placeholders are bound', 'ssss', $types);
same('a percent sign is escaped so it is not a wildcard', '%100\\%%', $params[0]);
[, , $params] = student_search_filter('a_b');
same('an underscore is escaped', '%a\\_b%', $params[0]);
[, , $params] = student_search_filter('back\\slash');
same('a backslash is escaped first', '%back\\\\slash%', $params[0]);

echo "\nsort whitelist\n";
same('a known column passes', 'email', student_sort_column('email'));
same('an unknown column falls back', 'first_name', student_sort_column('id; DROP TABLE students'));
same('a null column falls back', 'first_name', student_sort_column(null));
same('DESC is honoured', 'DESC', student_sort_direction('desc'));
same('anything else is ASC', 'ASC', student_sort_direction('; DELETE FROM students'));

printf("\n  %d passed, %d failed\n\n", $passed, $failed);
exit($failed > 0 ? 1 : 0);
