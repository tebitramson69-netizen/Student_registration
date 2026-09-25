<?php
/**
 * Student data access.
 *
 * Every query that touches the students table lives here, so the view files
 * contain no SQL and the same query cannot be written two slightly different
 * ways in two places.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

const STUDENTS_PER_PAGE = 15;

/** Columns the listing may be sorted by. */
const STUDENT_SORT_COLUMNS = ['id', 'first_name', 'last_name', 'email', 'telephone', 'created_at'];

/** Raised when an email is already registered. */
class DuplicateEmailException extends RuntimeException
{
}

/** MySQL's duplicate-key error number. */
const MYSQL_DUPLICATE_ENTRY = 1062;

/**
 * A sort column is an SQL identifier, which a prepared statement cannot bind,
 * so it is matched against the whitelist above instead of being escaped.
 */
function student_sort_column(?string $column): string
{
    return in_array($column, STUDENT_SORT_COLUMNS, true) ? $column : 'first_name';
}

function student_sort_direction(?string $direction): string
{
    return strtoupper((string)$direction) === 'DESC' ? 'DESC' : 'ASC';
}

/**
 * Build the WHERE clause for a search term.
 *
 * @return array{0: string, 1: string, 2: list<string>} [sql, bind types, params]
 */
function student_search_filter(string $search): array
{
    if ($search === '') {
        return ['', '', []];
    }

    // % and _ are wildcards inside LIKE, and \ escapes them. Without this a
    // search for "%" matches every student and "100_" matches "1000".
    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
    $like    = '%' . $escaped . '%';

    $sql = "WHERE first_name LIKE ?
               OR last_name  LIKE ?
               OR email      LIKE ?
               OR telephone  LIKE ?";

    return [$sql, 'ssss', [$like, $like, $like, $like]];
}

function count_students(mysqli $conn, string $search = ''): int
{
    [$where, $types, $params] = student_search_filter($search);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM students $where");
    if ($params !== []) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    return $total;
}

/**
 * @return list<array<string, mixed>>
 */
function find_students(
    mysqli $conn,
    string $search = '',
    string $sort = 'first_name',
    string $order = 'ASC',
    int $limit = STUDENTS_PER_PAGE,
    int $offset = 0
): array {
    [$where, $types, $params] = student_search_filter($search);
    $sort  = student_sort_column($sort);
    $order = student_sort_direction($order);

    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, email, telephone
         FROM students
         $where
         ORDER BY $sort $order, id ASC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * Every matching student, for the CSV export. No LIMIT, so the caller should
 * stream the rows rather than collect them.
 */
function stream_students(mysqli $conn, string $search = ''): mysqli_result
{
    [$where, $types, $params] = student_search_filter($search);

    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, email, telephone
         FROM students
         $where
         ORDER BY first_name ASC, last_name ASC, id ASC"
    );
    if ($params !== []) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    return $stmt->get_result();
}

function find_student(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, email, telephone FROM students WHERE id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $student ?: null;
}

/**
 * @throws DuplicateEmailException when the email is already registered
 */
function create_student(mysqli $conn, array $student): int
{
    try {
        $stmt = $conn->prepare(
            "INSERT INTO students (first_name, last_name, email, telephone)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssss',
            $student['first_name'],
            $student['last_name'],
            $student['email'],
            $student['telephone']
        );
        $stmt->execute();
        $id = (int)$conn->insert_id;
        $stmt->close();

        return $id;
    } catch (mysqli_sql_exception $e) {
        // Checking for the address first and inserting second leaves a race
        // between the two queries; the UNIQUE index is what actually prevents
        // a duplicate, so its error is what we translate.
        if ($e->getCode() === MYSQL_DUPLICATE_ENTRY) {
            throw new DuplicateEmailException('That email address is already registered.', 0, $e);
        }

        throw $e;
    }
}

/**
 * @throws DuplicateEmailException when the email belongs to another student
 */
function update_student(mysqli $conn, int $id, array $student): void
{
    try {
        $stmt = $conn->prepare(
            "UPDATE students
             SET first_name = ?, last_name = ?, email = ?, telephone = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            'ssssi',
            $student['first_name'],
            $student['last_name'],
            $student['email'],
            $student['telephone'],
            $id
        );
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === MYSQL_DUPLICATE_ENTRY) {
            throw new DuplicateEmailException('That email address is already registered.', 0, $e);
        }

        throw $e;
    }
}

/** @return bool whether a row was actually removed */
function delete_student(mysqli $conn, int $id): bool
{
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    return $deleted;
}

/**
 * Whether the created_at column exists.
 *
 * An install created before migrations/001 will not have it, and the dashboard
 * says so rather than failing.
 */
function students_have_created_at(mysqli $conn): bool
{
    static $exists = null;

    if ($exists === null) {
        $result = $conn->query("SHOW COLUMNS FROM students LIKE 'created_at'");
        $exists = $result->num_rows > 0;
        $result->free();
    }

    return $exists;
}

/**
 * Dashboard counters.
 *
 * The time-based figures are null when created_at is missing.
 *
 * @return array{total: int, today: ?int, week: ?int, month: ?int}
 */
function student_stats(mysqli $conn): array
{
    $stats = ['total' => count_students($conn), 'today' => null, 'week' => null, 'month' => null];

    if (!students_have_created_at($conn)) {
        return $stats;
    }

    $result = $conn->query(
        "SELECT
             SUM(created_at >= CURDATE())                      AS today,
             SUM(created_at >= (CURDATE() - INTERVAL 6 DAY))   AS week,
             SUM(created_at >= (CURDATE() - INTERVAL 29 DAY))  AS month
         FROM students"
    );
    $row = $result->fetch_assoc();
    $result->free();

    $stats['today'] = (int)($row['today'] ?? 0);
    $stats['week']  = (int)($row['week'] ?? 0);
    $stats['month'] = (int)($row['month'] ?? 0);

    return $stats;
}

/**
 * The most recently added students, for the dashboard.
 *
 * Falls back to highest id first when created_at is missing, since an
 * auto-increment id still orders by insertion.
 *
 * @return list<array<string, mixed>>
 */
function find_recent_students(mysqli $conn, int $limit = 5): array
{
    $orderBy = students_have_created_at($conn) ? 'created_at DESC, id DESC' : 'id DESC';

    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, email, telephone
         FROM students
         ORDER BY $orderBy
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}
