# Training Enrollment System (PHP + PDO)

Multi-table, database-driven web app for the IPT laboratory activity. A single
administrator manages courses and classes, records students, enrolls them into
classes with limited slots, cancels enrollments, and views reports.
Enrollment and cancellation run inside **database transactions** so slot counts
never drift.

## Features
- Course CRUD (with search) and class scheduling (course, schedule, instructor, slots)
- Record a new student + enrollment + slot decrement in **one transaction**
- Enroll an existing student (blocks full classes and duplicate active enrollments)
- Cancel an enrollment (status update + slot restore in **one transaction**)
- Enrollment list with INNER JOINs: search, status/course filter, sorting, pagination
- Reports using LEFT JOIN + GROUP BY
- Server-side validation, prepared statements only, output escaping, CSRF tokens, session login

## Requirements
PHP 8.0+ (7.4 works), MySQL/MariaDB with InnoDB, XAMPP recommended.

## Setup
1. Copy the folder to `C:\xampp\htdocs\training_enrollment\`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Open phpMyAdmin -> *Import* -> choose `sql/training_db.sql` (creates `training_db` with sample data).
4. (Optional) If your MySQL user/password differs from `root` / empty, copy
   `config/config.local.sample.php` to `config/config.local.php` and edit it
   (this file is git-ignored; never commit passwords).
5. Visit <http://localhost/training_enrollment/> and log in:
   **admin / admin123** (change the hash in `config/app.php`).

## Folder structure
```
config/    db.php (single PDO connection), app.php (admin account)
classes/   Database (Singleton), Course, ClassSection, Student, EnrollmentRepository, NoSlotsException, Pet (example)
admin/     courses, classes, students, enroll, enrollments, reports, login, logout
includes/  header, footer, helpers
css/       style.css
sql/       schema.sql, training_db.sql
docs/      DOCUMENTATION.md (copy into your Google Doc)
```

## Design patterns
- **Singleton** - `Database` extends PDO and exposes one shared connection via `getInstance()`.
- **Repository** - `EnrollmentRepository` hides SQL/transactions behind methods
  (`recordStudent`, `enroll`, `cancel`, `allWithDetails`).
See `docs/DOCUMENTATION.md` for the full explanation.

## Testing the transaction rollback
Class `NET110-A` is seeded with 0 slots. Recording a student into it shows
"No slots available" and no row is added to `students` or `enrollments`.
