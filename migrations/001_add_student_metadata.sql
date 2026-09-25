-- Migration 001 - for databases created before the dashboard and the email
-- uniqueness rule existed. A fresh install gets all of this from schema.sql
-- and does not need to run this file.
--
--     mysql -u root -p student_db < migrations/001_add_student_metadata.sql
--
-- IMPORTANT: the UNIQUE index below fails if two students already share an
-- email address. List any duplicates first and fix them by hand:
--
--     SELECT email, COUNT(*) AS copies
--     FROM students
--     GROUP BY email
--     HAVING copies > 1;

USE student_db;

-- Registration date, which the dashboard counters need. Existing rows get the
-- moment the migration runs, since their real registration date is not
-- recorded anywhere.
ALTER TABLE students
    ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Emails are stored lower-case by normalise_student(), so this index catches
-- "R.Titih@x.cm" and "r.titih@x.cm" as the same address.
UPDATE students SET email = LOWER(email);

ALTER TABLE students
    ADD UNIQUE KEY uniq_students_email (email);

-- Sorting and searching both hit the name columns.
ALTER TABLE students
    ADD INDEX idx_students_first_name (first_name),
    ADD INDEX idx_students_last_name (last_name);
