<?php
/**
 * Student validation rules.
 *
 * One place for the rules, used by form.php, update.php and the JSON API, so
 * the three cannot drift apart. assets/js/app.js mirrors these in the browser
 * for instant feedback; the server copy is the one that decides.
 */

declare(strict_types=1);

const NAME_MAX_LENGTH      = 100;
const EMAIL_MAX_LENGTH     = 255;
const TELEPHONE_MAX_LENGTH = 30;

/**
 * Normalise a submitted student, collapsing runs of whitespace inside names so
 * "Ramson   Titih" is stored the way it will be searched for.
 *
 * @return array{first_name: string, last_name: string, email: string, telephone: string}
 */
function normalise_student(array $input): array
{
    $clean = static fn(string $key): string => trim(
        preg_replace('/\s+/u', ' ', (string)($input[$key] ?? '')) ?? ''
    );

    return [
        'first_name' => $clean('first_name'),
        'last_name'  => $clean('last_name'),
        // Stored lower-case so "R.Titih@x.com" and "r.titih@x.com" cannot both
        // be registered; the UNIQUE index then actually catches duplicates.
        'email'      => strtolower($clean('email')),
        'telephone'  => $clean('telephone'),
    ];
}

/**
 * Validate a normalised student.
 *
 * @return array<string, string> field name => first error, empty when valid
 */
function validate_student(array $student): array
{
    $errors = [];

    foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
        $value = $student[$field] ?? '';

        if ($value === '') {
            $errors[$field] = "$label is required.";
        } elseif (mb_strlen($value) > NAME_MAX_LENGTH) {
            $errors[$field] = "$label cannot be longer than " . NAME_MAX_LENGTH . ' characters.';
        } elseif (!preg_match("/^[\p{L}\p{M}' .-]+$/u", $value)) {
            // \p{L} and \p{M} keep accented and non-Latin names valid, which a
            // plain [A-Za-z] check would have rejected.
            $errors[$field] = "$label may only contain letters, spaces, apostrophes, hyphens and full stops.";
        }
    }

    $email = $student['email'] ?? '';
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (mb_strlen($email) > EMAIL_MAX_LENGTH) {
        $errors['email'] = 'Email cannot be longer than ' . EMAIL_MAX_LENGTH . ' characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    $telephone = $student['telephone'] ?? '';
    if ($telephone === '') {
        $errors['telephone'] = 'Telephone is required.';
    } elseif (mb_strlen($telephone) > TELEPHONE_MAX_LENGTH) {
        $errors['telephone'] = 'Telephone cannot be longer than ' . TELEPHONE_MAX_LENGTH . ' characters.';
    } elseif (!preg_match('/^\+?[0-9 ()-]{6,}$/', $telephone)) {
        // Deliberately permissive: Cameroonian numbers are written 6XX XX XX XX
        // locally and +237 6XX XX XX XX internationally, and both must pass.
        $errors['telephone'] = 'Enter a valid telephone number, digits only, optionally starting with +.';
    } elseif (preg_match_all('/[0-9]/', $telephone) < 6) {
        $errors['telephone'] = 'Telephone must contain at least 6 digits.';
    }

    return $errors;
}
