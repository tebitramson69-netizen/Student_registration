---
title: School Management System
summary: A secondary-school management platform built for how Cameroonian schools actually grade, rank and report — coefficients, sequences and all.
role: Sole designer, architect and developer
category: Education platform
order: 1
featured: true
year: '2026'
status: In active development — core academic engine working
tech:
  - PHP 8
  - Custom MVC
  - MySQL / MariaDB
  - PDO
  - JavaScript
  - Bootstrap 5
  - Apache / XAMPP
  - Git
repo: https://github.com/tebitramson69-netizen/School-Management-System
metrics:
  - value: '18'
    label: Database tables
  - value: '4'
    label: Role-based dashboards
  - value: '~13k'
    label: Lines of PHP
---

## The problem

A Cameroonian secondary school runs on a grading system that almost no
off-the-shelf school software understands. Marks are out of 20. Ten out of
twenty is the pass line. The year splits into three terms, each term into two
sequences — six sequences in all. And critically, subjects are **not** equal:
every subject carries a MINESEC-standardised coefficient, and a student's
average is the weighted mean

> Σ(mark × coefficient) ÷ Σ(coefficients)

not the plain average of their marks.

Get that formula wrong and every number downstream is wrong too: the term
average, the class ranking, the pass/fail decision, the report card a parent
signs. Imported software built around a 0–100 scale and unweighted GPAs does
not just look foreign — it produces the wrong answer.

Meanwhile the actual work is still being done by hand. A class teacher collects
mark sheets from every subject teacher, computes weighted averages with a
calculator, sorts the class to find positions, and copies it all onto printed
report cards. It takes days per term, and a single transcription slip travels
silently into a student's permanent record.

## The goal

Build the system that does understand the Cameroonian model: one place where
subject teachers enter their own marks, where the weighted average and the class
ranking are computed rather than typed, and where an administrator, a teacher, a
student and a parent each see exactly the slice that belongs to them.

## My role

I built the whole thing — domain research, database schema, architecture,
security model, every controller and every view. There was no framework and no
starter template; the routing, authentication, authorisation and data layer are
code I wrote and now maintain.

## Architecture

I deliberately chose **no framework**. The target deployment is a school with a
shared Apache host or a local XAMPP machine and an administrator who is not a
developer — a `composer install` step is a failure mode, not a convenience. So
the application is plain PHP 8 with a structure I imposed myself:

```
public/index.php          front controller — every request enters here
  src/Controllers/        one per role: Admin, Teacher, Student, Parent, Auth
  src/Models/             one per table-group, PDO-backed
  src/Core/               School (DB singleton), Security (CSRF, sessions)
  src/Middleware/         AuthMiddleware — role gates
  views/                  layouts, components, per-role pages
  database/               schema.sql + numbered migrations
```

A single front controller with an `?action=` switch routes all 37 endpoints.
That is not the most fashionable routing design, and it was the right one here:
it works on any Apache configuration without rewrite rules, which is precisely
the environment this has to survive in.

### Modelling the academic year

The schema's centre of gravity is the academic calendar, because everything else
hangs off it. `academic_years` and `terms` each carry an `is_current` flag with
the invariant that exactly one row is true at a time. `enrollments` ties a
student to a class *for a given year*, so a student's history survives their
promotion. `scores` is uniquely keyed on student + subject + term + sequence,
which makes double-entry structurally impossible rather than merely discouraged.

`subject_coefficients` is per-subject **and** per-class, because a science
subject does not carry the same weight in Form 3 as it does in Upper Sixth, and
because the ministry's numbers should be a default an administrator can adjust —
not a constant compiled into the source.

## Security decisions

This system holds minors' academic records, so the security work was not left
until the end:

- **CSRF protection is global, not per-form.** `Security::requireValidCsrf()`
  runs in the front controller against every POST request. A developer adding a
  new form cannot forget to protect it, because protection is not their job to
  remember — it is the router's. Tokens are compared with `hash_equals()`.
- **Sessions are hardened before they start** — `httponly`, `samesite=Strict`,
  `use_strict_mode`, `use_only_cookies`, and the `secure` flag added
  automatically when the request is HTTPS.
- **Login is rate limited** to five attempts followed by a five-minute lockout.
- **Credentials live in `.env`**, never in a committed file.
- **Every query goes through PDO** with bound parameters, and every value
  printed into a page goes through `htmlspecialchars()`.

## The hardest problem

The weighted average sounds like a one-line formula. Implementing it correctly
was the single most invasive change in the project.

When I audited my own codebase I found the averaging logic had been duplicated
in three places, and all three ignored coefficients entirely. Worse, the class
ranking was computed from a *different* average than the one displayed on the
student's own page — so a student could read a 13.4 on their dashboard and be
ranked as though they had something else. Two numbers, both presented as truth,
disagreeing with each other.

The fix was not just to add the multiplication. It was to make one function the
only place an average is ever produced, delete the other two, and re-point class
ranking at it so the ranking and the report card are mathematically the same
statement. A displayed number and a ranked number that disagree is worse than
either being wrong alone, because it destroys trust in the whole system.

## What works today

- **Administration** — create and manage teachers, students, parents and
  classes; assign teachers to class/subject pairs; open and close academic years
  and set the current term; configure per-class subject coefficients.
- **Teaching** — subject teachers enter sequence scores for their own assigned
  classes only, and mark attendance.
- **Students** — a dashboard with their marks, attendance, announcements and a
  report card carrying their coefficient-weighted average and class position.
- **Parents** — a read-only view scoped to their own children through the
  `parent_student` relationship.
- **Announcements** — posted by administrators, surfaced per role.

## What I would tell you in an interview

The audit is the part I am most pleased with. Partway through I stopped adding
features and reviewed my own code as if someone else had written it — and wrote
down every finding, including the embarrassing ones: dead duplicate functions,
model files whose casing worked on my Windows machine and would break the moment
the project touched a Linux server, a password policy that demanded eight
characters when changing a password but accepted six when creating one.

That list lives in the repository as `PROJECT_PROGRESS.md`. Publishing your own
bug list is uncomfortable. It is also the only way I found those bugs before a
school did.

## What is not done yet

Printable PDF report cards, full CRUD for every entity, and a unified layout
across all four dashboards are still in progress. The roadmap in the repository
is honest about which phase each of those sits in.
