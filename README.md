# Student Registration Project

An admin-only PHP + MySQL system for managing student records.

- Admin sign-in with hashed passwords and brute-force throttling
- Student registration, editing and deletion (admins only)
- Search, sorting and pagination
- CSV export
- Styled with modern CSS

All database access uses **prepared statements**, all state-changing forms
carry a **CSRF token**, and all output is escaped.

## Project structure

| File | Purpose |
|---|---|
| `connection.php` | Creates the MySQLi connection and loads credentials |
| `config.example.php` | Template for local/production credentials |
| `auth.php` | Sessions, login guard, CSRF tokens, login throttling |
| `helpers.php` | Shared view helpers (HTML escaping) |
| `login.php` / `logout.php` | Admin sign in and sign out |
| `create_admin.php` | CLI script that creates an admin account |
| `form.php` | Register a student |
| `table.php` | Listing with search, sorting and pagination |
| `update.php` | Edit a student |
| `delete.php` | Delete a student (POST only) |
| `export.php` | CSV download |
| `schema.sql` | Database schema |
| `style.css` | Stylesheet |

## Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/tebitramson69-netizen/Student_registration.git
   ```

2. Create the database and tables:
   ```bash
   mysql -u root -p < schema.sql
   ```

3. Configure the credentials:
   ```bash
   cp config.example.php config.php
   ```
   Then edit `config.php`. It is git-ignored and must never be committed.
   If you skip this step the local XAMPP defaults (`root`, empty password)
   are used, which is fine for development only.

4. Create the first admin account (command line only, minimum 10 characters):
   ```bash
   php create_admin.php
   ```

5. Copy the project into your XAMPP `htdocs` folder and open
   <http://localhost/Student_registration/login.php>.

## Security notes

| Concern | How it is handled |
|---|---|
| SQL injection | Prepared statements everywhere; `ORDER BY` column and direction validated against a whitelist, since identifiers cannot be bound |
| XSS | All output escaped through `e()` with `ENT_QUOTES` |
| CSRF | Per-session token on every state-changing form, compared with `hash_equals()`; `delete.php` and `logout.php` accept POST only |
| Passwords | `password_hash()` / `password_verify()`, rehashed automatically when PHP's default cost rises |
| Session fixation | `session_regenerate_id(true)` on login |
| Session theft | `HttpOnly`, `SameSite=Lax`, and `Secure` when served over HTTPS |
| Idle sessions | Signed out after 30 minutes of inactivity |
| Brute force | 5 failed attempts per username+IP locks that pair out for 15 minutes |
| Username enumeration | One error message for both cases, and a dummy hash verification so a missing user takes the same time |
| Error disclosure | Database errors are logged, never printed to the visitor |

## Continuous integration

`.github/workflows/php-ci.yml` runs `php -l` over every PHP file on each push
and pull request, so a syntax error can never reach `main`.

## Known limitations

- There is no "forgot password" flow; use `create_admin.php` to add accounts.
- All admins have the same rights — there are no roles.
- `login_attempts` rows are never purged; add a scheduled cleanup if the
  table grows.
- Serve the site over HTTPS in production, otherwise the session cookie and
  the admin password travel in clear text.
