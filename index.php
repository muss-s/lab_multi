<?php
// index.php
$base = '';
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>
<h1>Training Enrollment System</h1>
<div class="card">
    <p>Welcome! This system lets the administrator manage training courses and class schedules,
       record students, enroll them into classes with limited slots, cancel enrollments, and
       view reports. Enrollment and cancellation run inside database transactions so slot counts
       always stay consistent.</p>
    <?php if (empty($_SESSION['admin'])): ?>
        <p><a class="btn" href="admin/login.php">Log in as administrator</a></p>
    <?php endif; ?>
</div>
<div class="grid">
    <a class="card" href="admin/courses.php"><span class="ico">&#128193;</span><strong>Courses</strong><br>Add, edit, delete courses</a>
    <a class="card" href="admin/classes.php"><span class="ico">&#128193;</span><strong>Classes</strong><br>Schedules, instructors, slots</a>
    <a class="card" href="admin/students.php"><span class="ico">&#128193;</span><strong>Record Student</strong><br>New student + enrollment</a>
    <a class="card" href="admin/enroll.php"><span class="ico">&#128193;</span><strong>Enroll</strong><br>Existing student into a class</a>
    <a class="card" href="admin/enrollments.php"><span class="ico">&#128193;</span><strong>Enrollments</strong><br>Search, filter, cancel</a>
    <a class="card" href="admin/reports.php"><span class="ico">&#128193;</span><strong>Reports</strong><br>Summaries per course/class</a>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
