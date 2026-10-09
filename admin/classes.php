<?php
// admin/classes.php -- Manage class schedules and slots
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_login($base);

$courses = new Course($db);
$classes = new ClassSection($db);
$errors  = [];
$edit    = null;
$form    = ['course_id' => '', 'class_code' => '', 'schedule' => '', 'instructor' => '', 'slots' => '10'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['class_id'] ?? 0);

    if ($action === 'delete') {
        try {
            $classes->delete($id);
            flash('success', 'Class deleted.');
        } catch (PDOException $ex) {
            flash('error', 'Cannot delete this class because it has enrollment records.');
        }
        redirect('classes.php');
    }

    $course_id  = (int) ($_POST['course_id'] ?? 0);
    $code       = trim($_POST['class_code'] ?? '');
    $schedule   = trim($_POST['schedule'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $slots      = filter_var($_POST['slots'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000]]);

    if (!$courses->find($course_id))                    { $errors[] = 'Please choose a valid course.'; }
    if ($code === '' || strlen($code) > 20)             { $errors[] = 'Class code is required (max 20 chars).'; }
    if (strlen($schedule) > 100)                        { $errors[] = 'Schedule is too long (max 100 chars).'; }
    if (strlen($instructor) > 100)                      { $errors[] = 'Instructor name is too long (max 100 chars).'; }
    if ($slots === false)                               { $errors[] = 'Slots must be a whole number from 0 to 1000.'; }
    if ($action === 'update' && !$classes->find($id))   { $errors[] = 'Class not found.'; }

    if (!$errors) {
        if ($action === 'update') {
            $classes->update($id, $course_id, $code, $schedule, $instructor, $slots);
            flash('success', 'Class updated.');
        } else {
            $classes->create($course_id, $code, $schedule, $instructor, $slots);
            flash('success', 'Class added.');
        }
        redirect('classes.php');
    }
    $form = ['course_id' => $course_id, 'class_code' => $code, 'schedule' => $schedule,
             'instructor' => $instructor, 'slots' => $_POST['slots'] ?? ''];
    if ($action === 'update') { $edit = ['class_id' => $id]; }
} elseif (isset($_GET['edit'])) {
    $edit = $classes->find((int) $_GET['edit']);
    if ($edit) { $form = $edit; }
}

$courseList = $courses->all();
$list       = $classes->allWithCourse();

$pageTitle = 'Classes';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Classes</h1>

<div class="card">
    <h2><?= $edit ? 'Edit Class' : 'Add Class' ?></h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="class_id" value="<?= (int) $edit['class_id'] ?>"><?php endif; ?>
        <label for="course_id">Course</label>
        <select id="course_id" name="course_id" required>
            <option value="">-- choose course --</option>
            <?php foreach ($courseList as $c): ?>
                <option value="<?= (int) $c['course_id'] ?>" <?= (int) $form['course_id'] === (int) $c['course_id'] ? 'selected' : '' ?>>
                    <?= e($c['course_code'] . ' - ' . $c['course_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="class_code">Class code</label>
        <input type="text" id="class_code" name="class_code" maxlength="20" value="<?= e($form['class_code']) ?>" required>
        <label for="schedule">Schedule</label>
        <input type="text" id="schedule" name="schedule" maxlength="100" placeholder="e.g. Mon/Wed 9:00-11:00 AM" value="<?= e($form['schedule']) ?>">
        <label for="instructor">Instructor</label>
        <input type="text" id="instructor" name="instructor" maxlength="100" value="<?= e($form['instructor']) ?>">
        <label for="slots">Available slots</label>
        <input type="number" id="slots" name="slots" min="0" max="1000" value="<?= e($form['slots']) ?>" required>
        <p>
            <button type="submit"><?= $edit ? 'Update class' : 'Add class' ?></button>
            <?php if ($edit): ?><a class="btn secondary" href="classes.php">Cancel</a><?php endif; ?>
        </p>
    </form>
</div>

<table>
    <thead><tr><th>ID</th><th>Course</th><th>Class</th><th>Schedule</th><th>Instructor</th><th>Slots left</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($list as $c): ?>
        <tr>
            <td><?= (int) $c['class_id'] ?></td>
            <td><?= e($c['course_name']) ?></td>
            <td><?= e($c['class_code']) ?></td>
            <td><?= e($c['schedule']) ?></td>
            <td><?= e($c['instructor']) ?></td>
            <td><?= (int) $c['slots'] ?> <?php if ((int) $c['slots'] === 0): ?><span class="badge full">FULL</span><?php endif; ?></td>
            <td>
                <a class="btn secondary" href="classes.php?edit=<?= (int) $c['class_id'] ?>">Edit</a>
                <form method="post" class="inline" onsubmit="return confirm('Delete this class?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="class_id" value="<?= (int) $c['class_id'] ?>">
                    <button type="submit" class="danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="7">No classes yet.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
