# Training Enrollment System - Documentation

**Course:** IPT - Integrative Programming and Technology
**Student:** Remus Aimiel Marasigan
**GitHub repository:** <paste public URL>
**Date:** October 9, 2026

## 1. Project description
The Training Enrollment System is a PHP + PDO + MySQL web application for an organization that
offers training programs. An administrator registers courses, schedules classes (schedule,
instructor, slots), records students, enrolls them, cancels enrollments, and generates reports.
The system prevents over-enrollment and duplicate enrollments, and keeps data consistent using
database transactions.

## 2. Setup instructions
1. Place the project in `C:\xampp\htdocs\training_enrollment\`.
2. Start Apache and MySQL in XAMPP.
3. Import `sql/training_db.sql` in phpMyAdmin.
4. Open `http://localhost/training_enrollment/` and log in with `admin` / `admin123`.
5. If needed, create `config/config.local.php` (copy of the sample) for custom DB credentials.

## 3. Database design
Tables: `students`, `courses`, `classes`, `enrollments`. `classes.course_id` references `courses`;
`enrollments` is the junction table resolving the many-to-many relationship between students and
classes (`student_id`, `class_id` foreign keys). `classes.slots` stores the remaining slots.
All tables use InnoDB so transactions and foreign keys work.

## 4. Design patterns used and why
**Singleton - `Database`.** `Database` extends PDO, has a private constructor, and is obtained
through `Database::getInstance()`. The whole application therefore shares exactly one connection:
no repeated connection overhead, and one place to set error mode (exceptions) and fetch mode.

**Repository - `EnrollmentRepository`.** All enrollment-related data access (record student,
enroll, cancel, joined listings, reports) is behind one class. Pages in `admin/` only handle the
request and display; they never contain SQL. This separates presentation from persistence and
makes the transactional logic reusable and easy to test.

**Transactions (unit of work).** `recordStudent()` runs: begin -> lock class row and check slots
-> insert student -> insert enrollment -> decrement slot -> commit. Any failure triggers
`rollBack()`, so no partial rows remain. `cancel()` updates the status and restores the slot in a
single transaction. `SELECT ... FOR UPDATE` and `UPDATE ... WHERE slots > 0` prevent two
simultaneous requests from taking the last slot.

## 5. Security measures
Prepared statements everywhere (no user input concatenated into SQL; sort columns use a
whitelist), `htmlspecialchars` output escaping, CSRF tokens on every POST form, session-based
administrator login, server-side validation, no secrets committed (`config.local.php` is ignored).

## 6. Screenshot evidence
> Run the app, capture each page (include the address bar), save images in `docs/screenshots/`,
> then paste them into your Google Doc under the matching headings.

### Requirement 1 - Course Management
![Courses list](screenshots/01-courses.png)
*Caption: `admin/courses.php` listing all courses.*

### Requirement 2 - Add / Edit Course
![Add/Edit course](screenshots/02-course-form.png)
*Caption: Filled course form and the updated list showing the new/edited record.*

### Requirement 3 - Class Management
![Classes](screenshots/03-classes.png)
*Caption: Classes with course, schedule and remaining slots.*

### Requirement 4 - Student recording form
![Student form](screenshots/04-student-form.png)
*Caption: Form with name, email, phone and class dropdown.*

### Requirement 5 - Successful recording
![Success](screenshots/05-success.png)
*Caption: Confirmation message; new rows in `students` and `enrollments`; the class has one fewer slot.*

### Requirement 6 - Enrollments list
![Enrollments](screenshots/06-enrollments.png)
*Caption: Enrollments joined with student, class and course details.*

### Requirement 7 - Cancelled enrollment
![Cancelled](screenshots/07-cancelled.png)
*Caption: Enrollment with status `cancelled` and the class slot count restored.*

### Requirement 8 - Rollback case
![Rollback](screenshots/08-rollback.png)
*Caption: Recording into full class `NET110-A` shows "No slots available"; `students` and `enrollments` have no partial rows.*

### Requirement 9 - Database evidence
![phpMyAdmin](screenshots/09-database.png)
*Caption: phpMyAdmin view of `students`, `enrollments` and `classes` with the saved rows.*

## 7. Submission links
- Google Document URL: <paste, "Anyone with the link can view">
- GitHub repository URL: <paste, public>
