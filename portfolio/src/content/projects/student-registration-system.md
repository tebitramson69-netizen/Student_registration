---
title: Student Registration System
summary: The first thing I built end to end — a PHP and MySQL CRUD application with search, sorting, pagination and CSV export.
role: Sole developer
category: Foundations
order: 3
featured: false
year: '2025'
status: Complete — kept public as a reference point
tech:
  - PHP
  - MySQL
  - HTML
  - CSS
  - Apache / XAMPP
  - Git
repo: https://github.com/tebitramson69-netizen/Student_registration
---

## What it is

A student registration system: a form that captures student records into MySQL,
a table that lists them with search, column sorting and pagination, update and
delete actions, and a CSV export for anyone who needs the data in a spreadsheet.

Small. It is also the project where I learned what a web application actually
*is* — that a form is an HTTP POST, that a redirect after a successful insert is
what stops a refresh creating a duplicate, that pagination is arithmetic on an
offset, and that a table with a thousand rows behaves nothing like a table with
five.

## Why it is still here

I could quietly delete this repository. I have kept it public on purpose,
because the honest version of a portfolio shows the distance travelled, and
because I can now read my own early code and say precisely what is wrong with
it.

**Queries are built by string interpolation**, escaped with
`mysqli_real_escape_string()` rather than parameterised. The sort column is
whitelisted against a fixed array — which is the one thing I got right there,
since a column name cannot be a bound parameter — but the values should never
have been concatenated into SQL at all. Everything I have written since uses PDO
with bound parameters.

**Database credentials are hard-coded** in `connection.php` and committed. They
belong in a `.env` file that `.gitignore` never lets near a repository.

**There is no authentication.** Anyone who can reach `delete.php?id=5` can
delete student number five. My later work puts a role check in front of every
destructive route as a matter of course.

**Output escaping is inconsistent** — the table escapes, the update form mostly
escapes, and that "mostly" is exactly where a stored XSS lives.

## What I took from it

Every one of those four faults is a habit I now have in the opposite direction,
and I have them because I built this first and then had to look at it again. The
School Management System has global CSRF validation, hardened sessions, rate
limiting and externalised credentials — not because I read that those were best
practices, but because I had already written the version without them.
