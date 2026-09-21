# Student Registration Project

A simple PHP + MySQL project that demonstrates:
- Student registration form with server-side validation
- Search, sorting, and pagination
- CSV export feature
- Styled with modern CSS

All database access uses **prepared statements**, so user input is never
concatenated into SQL.

## Project structure

| File | Purpose |
|---|---|
| `connection.php` | Creates the MySQLi connection and loads credentials |
| `config.example.php` | Template for local/production credentials |
| `helpers.php` | Shared view helpers (HTML escaping) |
| `form.php` | Registration form and insert |
| `table.php` | Listing with search, sorting and pagination |
| `update.php` | Edit an existing student |
| `delete.php` | Delete a student |
| `export.php` | CSV download |
| `style.css` | Stylesheet |

## Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/tebitramson69-netizen/Student_registration.git
   ```

2. Create the database and table:
   ```sql
   CREATE DATABASE student_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE student_db;

   CREATE TABLE students (
       id         INT AUTO_INCREMENT PRIMARY KEY,
       first_name VARCHAR(100) NOT NULL,
       last_name  VARCHAR(100) NOT NULL,
       email      VARCHAR(255) NOT NULL,
       telephone  VARCHAR(30)  NOT NULL,
       INDEX idx_students_last_name (last_name),
       INDEX idx_students_first_name (first_name)
   ) ENGINE=InnoDB;
   ```

3. Configure the credentials:
   ```bash
   cp config.example.php config.php
   ```
   Then edit `config.php`. It is git-ignored and must never be committed.
   If you skip this step the local XAMPP defaults (`root`, empty password)
   are used, which is fine for development only.

4. Copy the project into your XAMPP `htdocs` folder and open
   <http://localhost/Student_registration/form.php>.

## Continuous integration

`.github/workflows/php-ci.yml` runs `php -l` over every PHP file on each push
and pull request, so a syntax error can never reach `main`.

## Not implemented yet

This application has **no authentication and no CSRF protection**. Anyone who
can reach the pages can list, edit and delete students, and `delete.php` acts
on a `GET` request. Do not expose it on a public server until an admin login
and CSRF tokens are added.
