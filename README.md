# Student Registry

An admin-only student records system built with plain PHP, MySQL and vanilla
JavaScript. No framework, no build step: copy it into `htdocs` and it runs.

- Admin sign-in with hashed passwords, brute-force throttling and idle timeout
- Dashboard with registration figures
- Student register with live search, sortable columns and pagination
- CSV export
- Responsive, accessible interface with a light and a dark theme

## Architecture

The app is layered so that no file does more than one job.

Only the pages themselves sit in the web root. Everything that is `require`d
lives in `includes/`, which its own `.htaccess` denies over HTTP.

```
  /                      pages: index, table, form, update, delete,
  |                      export, login, logout
  |
  +-- api/               list-students.php - the JSON endpoint
  +-- assets/            app.css, app.js
  +-- includes/          denied over HTTP
  |     auth.php         session, login guard, CSRF tokens, throttling
  |     layout.php       shared page chrome
  |     students.php     every query against the students table
  |     validation.php   the rules, shared by the pages and the API
  |     helpers.php      escaping, pagination, formatting, flash messages
  |     connection.php   the MySQLi connection
  |
  +-- bin/               create_admin.php (CLI only)
  +-- tests/             run.php
  +-- migrations/        schema upgrades
```

`api/list-students.php` serves the same data as JSON. `assets/js/app.js` calls
it so searching, sorting and paging happen without a page reload.

| File | Purpose |
|---|---|
| `index.php` | Dashboard |
| `table.php` | Student register |
| `form.php` / `update.php` / `delete.php` | Create, edit, delete |
| `export.php` | CSV download |
| `login.php` / `logout.php` | Sign in and out |
| `bin/create_admin.php` | CLI script that creates an admin account |
| `api/list-students.php` | JSON listing endpoint |
| `assets/css/app.css` | Design tokens and components |
| `assets/js/app.js` | Live search, sorting, dialogs, validation, theme |
| `schema.sql` / `migrations/` | Database schema |
| `tests/run.php` | Unit tests |

## Progressive enhancement

Every page renders complete HTML on the server and every form and link works on
its own. JavaScript then upgrades the experience:

| Without JavaScript | With JavaScript |
|---|---|
| Search submits and reloads the page | Searches as you type, no reload |
| Column headers are links | Sorting swaps the rows in place |
| Pagination links reload | Pages swap in place, URL kept in sync |
| Delete submits straight away | An accessible dialog confirms first |
| Errors appear after the round trip | Fields are checked as you leave them |

The server validates every submission again regardless. The browser copy in
`app.js` exists for speed of feedback, not for safety.

## Setup

1. Create the database and tables:
   ```bash
   mysql -u root -p < schema.sql
   ```
   Upgrading an existing install instead? Run the migration:
   ```bash
   mysql -u root -p student_db < migrations/001_add_student_metadata.sql
   ```

2. Configure the credentials:
   ```bash
   cp includes/config.example.php includes/config.php
   ```
   Edit `includes/config.php`; it is git-ignored and must never be committed. Skipping
   this step falls back to the XAMPP defaults, which is fine for development
   only.

3. Create the first admin (command line only, minimum 10 characters):
   ```bash
   php bin/create_admin.php
   ```

4. Copy the project into `htdocs` and open
   <http://localhost/Student_registration/>.

Apache needs `AllowOverride All` for the bundled `.htaccess` to take effect. On
a default XAMPP install it already does.

## Security

| Concern | How it is handled |
|---|---|
| SQL injection | Prepared statements everywhere; the `ORDER BY` column and direction are whitelisted, since identifiers cannot be bound |
| XSS | Server output escaped through `e()` with `ENT_QUOTES`; the JavaScript writes values with `textContent`, never `innerHTML` |
| CSRF | Per-session token on every state-changing form, compared with `hash_equals()`; `delete.php` and `logout.php` are POST-only |
| Content injection | A strict `Content-Security-Policy` with no `unsafe-inline`, which is why no page uses an inline `<script>`, `onclick` or `style` attribute |
| CSV formula injection | Cells starting with `=`, `+`, `-` or `@` are prefixed so a spreadsheet treats them as text |
| Passwords | `password_hash()` / `password_verify()`, rehashed when PHP's default cost rises |
| Session fixation | `session_regenerate_id(true)` on login |
| Session theft | `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS |
| Idle sessions | Signed out after 30 minutes |
| Brute force | 5 failed attempts per username+IP locks that pair for 15 minutes |
| Username enumeration | One message for both cases, plus a dummy hash verification so a missing user takes the same time |
| File disclosure | Every `require`d file lives in `includes/`, denied over HTTP by its own `.htaccess`, as are `bin/`, `tests/` and `migrations/`; `.sql`, `.md` and dotfiles are denied by extension |
| Error disclosure | Database errors are logged, never printed |

## Accessibility

Contrast was computed, not eyeballed: every foreground/background pair meets
WCAG AA on its own surface, in both themes. Dark mode uses its own steps chosen
against the dark surface rather than an inverted copy of the light ones.

Also: a skip link, visible focus rings, `aria-sort` on sorted columns, an
`aria-live` region announcing result counts, `aria-invalid` with matching error
text on invalid fields, labels on every control, and status carried by an icon
and a word rather than colour alone. `prefers-reduced-motion` disables the
animations.

## Tests

```bash
php tests/run.php     # 37 unit tests, no database needed
```

CI runs `php -l` over every PHP file, `node --check` over the JavaScript, and
the unit suite, on every push and pull request.

The browser behaviour was verified separately with Playwright against a stubbed
data layer (43 checks): live search, sorting, pagination, history handling, the
delete dialog, client-side validation, theme persistence, mobile layout, and
that a student whose name contains markup is rendered as text.

## Known limitations

- No password reset; use `bin/create_admin.php` to add accounts.
- All admins have the same rights - there are no roles.
- Deleting is permanent; there is no soft delete or audit trail.
- CSV cells that begin with `+`, `-`, `=` or `@` (telephone numbers, mostly) are
  written with a leading apostrophe. Excel and LibreOffice strip it on display;
  a plain text editor shows it.
- `login_attempts` rows are never purged; add a scheduled cleanup if it grows.
- Serve over HTTPS in production, and uncomment the HSTS header in `.htaccess`
  once a certificate is in place.
