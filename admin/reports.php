<?php
// admin/reports.php -- Summary reports (LEFT JOIN + GROUP BY)
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_login($base);

$repo      = new EnrollmentRepository($db);
$byCourse  = $repo->reportByCourse();
$byClass   = $repo->reportByClass();
$unenrolled = $repo->studentsWithoutActiveEnrollment();

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Reports</h1>

<h2>Enrollments per course</h2>
<table>
    <thead><tr><th>Code</th><th>Course</th><th>Classes</th><th>Active</th><th>Cancelled</th></tr></thead>
    <tbody>
    <?php foreach ($byCourse as $r): ?>
        <tr><td><?= e($r['course_code']) ?></td><td><?= e($r['course_name']) ?></td>
            <td><?= (int) $r['classes'] ?></td><td><?= (int) $r['active_enrollments'] ?></td>
            <td><?= (int) $r['cancelled_enrollments'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Class occupancy</h2>
<table>
    <thead><tr><th>Class</th><th>Course</th><th>Schedule</th><th>Instructor</th><th>Active enrollments</th><th>Slots left</th></tr></thead>
    <tbody>
    <?php foreach ($byClass as $r): ?>
        <tr><td><?= e($r['class_code']) ?></td><td><?= e($r['course_name']) ?></td>
            <td><?= e($r['schedule']) ?></td><td><?= e($r['instructor']) ?></td>
            <td><?= (int) $r['active_enrollments'] ?></td>
            <td><?= (int) $r['slots_left'] ?> <?php if ((int) $r['slots_left'] === 0): ?><span class="badge full">FULL</span><?php endif; ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Students with no active enrollment</h2>
<table>
    <thead><tr><th>ID</th><th>Name</th><th>Email</th></tr></thead>
    <tbody>
    <?php foreach ($unenrolled as $r): ?>
        <tr><td><?= (int) $r['student_id'] ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['email']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$unenrolled): ?><tr><td colspan="3">Every student has an active enrollment.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
