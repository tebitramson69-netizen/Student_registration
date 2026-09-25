# FeeBook

Fee collection and student records for small private training centres,
driving schools and vocational institutes in Cameroon.

It answers the three questions an owner actually has:
**who is enrolled, who has paid, and who still owes.**

Cash and Mobile Money are recorded the same way. The product digitises the
**receipt**, not the payment — because most tuition here is paid in cash and
will stay that way.

## Requirements

PHP 8.1+ (`pdo_mysql`) and MySQL 5.7+ / MariaDB 10.3+. XAMPP has both.

## Setup

```bash
# 1. Create the database and tables
mysql -u root -p < app/schema.sql

# 2. Create a database user for the application (do not use root)
mysql -u root -p -e "CREATE USER 'feebook'@'localhost' IDENTIFIED BY 'a-strong-password';
                     GRANT ALL ON feebook.* TO 'feebook'@'localhost';"

# 3. Configure
cp app/config/config.example.php app/config/config.php
# then edit app/config/config.php with your password and base_url

# 4. Create the centre and its owner login
php app/bin/create_centre.php "Centre name" "677000000" "Address" PREFIX \
    "Owner name" owner@example.com "a-strong-password"
```

Point Apache at `app/public/` and sign in. `app/public/` is the only directory
that should be web-accessible — `config/`, `src/` and `bin/` must stay outside
the document root on a live server.

To load demo data for a sales demonstration (never on a real centre):

```bash
php app/bin/seed_demo.php <institution_id> <user_id>
```

## What it does

| Page | Purpose |
|---|---|
| `index.php` | Collected this month, total still owed, latest receipts |
| `students.php` | Search, sort and page through students |
| `student_view.php` | One student: programmes, balance, full payment history |
| `programmes.php` | The courses the centre sells, and their fees |
| `payment_new.php` | Record a payment (cash, MoMo, Orange, bank, cheque) |
| `receipt.php` | Printable numbered receipt + ready-to-send SMS/WhatsApp text |
| `verify.php` | **Public.** Anyone can check a receipt code is genuine |
| `arrears.php` | Who is behind, how much, and their phone number |
| `export.php` | CSV of collections, optionally `?month=YYYY-MM` |

## Design decisions worth knowing

- **Payments are append-only.** A mistake is corrected with a `reversal` entry
  that points at the original; nothing is ever edited or deleted. This is what
  makes the record defensible when a student says "I already paid".
- **Balances are always derived** from the ledger, never stored in a column, so
  they cannot drift out of sync.
- **Money is stored as whole FCFA integers.** Never floats.
- **Every query is a prepared statement** and every query is scoped by
  `institution_id`, so one centre can never see another's data.
- **`agreed_fee_fcfa` is copied onto the enrolment** at enrolment time, so
  raising a programme's price never rewrites what existing students owe.
- **Receipt numbers are allocated under a row lock**, so two people recording
  payments at the same time cannot be issued the same number.
- Pages are server-rendered with no JavaScript framework — they have to load on
  a cheap Android phone over 3G.

## Not built yet (deliberately)

Grades, timetables, report cards, attendance, payroll, a parent portal and a
mobile app are all out of scope. Mobile Money collection through CamPay or
Fapshi comes in Phase 2 — and when it does, funds settle directly to the
centre's own account. **This application never holds customer money.**
