<?php
// admin/enroll.php -- Enroll an EXISTING student into a class
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Student.php';
require_login($base);

$repo     = new EnrollmentRepository($db);
$classes  = new ClassSection($db);
$students = new Student($db);
$errors   = [];
$student_id = $class_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_id   = (int) ($_POST['class_id'] ?? 0);

    if ($student_id <= 0 || !$students->find($student_id)) { $errors[] = 'Please choose a valid student.'; }
    if ($class_id <= 0 || !$classes->find($class_id))      { $errors[] = 'Please choose a valid class.'; }

    if (!$errors) {
        try {
            $repo->enroll($student_id, $class_id);
            flash('success', 'Student enrolled successfully.');
            redirect('enroll.php');
        } catch (NoSlotsException $ex) {
            $errors[] = 'No slots available in the selected class. Nothing was saved.';
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$studentList = $students->all();
$classList   = $classes->allWithCourse();
$pageTitle   = 'Enroll';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Enroll Existing Student</h1>
<div class="card" style="max-width:640px">
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post" class="stack">
        <?= csrf_field() ?>
        <label for="student_id">Student</label>
        <select id="student_id" name="student_id" required>
            <option value="">-- choose student --</option>
            <?php foreach ($studentList as $s): ?>
                <option value="<?= (int) $s['student_id'] ?>" <?= $student_id === (int) $s['student_id'] ? 'selected' : '' ?>>
                    <?= e($s['full_name']) ?><?= $s['email'] ? ' (' . e($s['email']) . ')' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="class_id">Class</label>
        <select id="class_id" name="class_id" required>
            <option value="">-- choose class --</option>
            <?php foreach ($classList as $c): ?>
                <option value="<?= (int) $c['class_id'] ?>" <?= $class_id === (int) $c['class_id'] ? 'selected' : '' ?>>
                    <?= e($c['course_name'] . ' | ' . $c['class_code'] . ' | ' . $c['schedule']) ?>
                    (<?= (int) $c['slots'] > 0 ? (int) $c['slots'] . ' slots left' : 'FULL' ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p><button type="submit">Enroll</button></p>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
