<?php
// admin/courses.php -- Manage courses (CRUD + search)
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Course.php';
require_login($base);

$courses = new Course($db);
$errors  = [];
$edit    = null;
$form    = ['course_code' => '', 'course_name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['course_id'] ?? 0);

    if ($action === 'delete') {
        try {
            $courses->delete($id);
            flash('success', 'Course deleted.');
        } catch (PDOException $ex) {
            flash('error', 'Cannot delete this course because it still has classes.');
        }
        redirect('courses.php');
    }

    $code = trim($_POST['course_code'] ?? '');
    $name = trim($_POST['course_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($code === '' || strlen($code) > 20 || !preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
        $errors[] = 'Course code is required (max 20 chars; letters, numbers, - and _ only).';
    }
    if ($name === '' || strlen($name) > 100) { $errors[] = 'Course name is required (max 100 chars).'; }
    if (strlen($desc) > 2000)                { $errors[] = 'Description is too long (max 2000 chars).'; }
    if ($action === 'update' && !$courses->find($id)) { $errors[] = 'Course not found.'; }

    if (!$errors) {
        try {
            if ($action === 'update') {
                $courses->update($id, $code, $name, $desc);
                flash('success', 'Course updated.');
            } else {
                $courses->create($code, $name, $desc);
                flash('success', 'Course added.');
            }
            redirect('courses.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') { $errors[] = 'That course code already exists.'; }
            else { throw $ex; }
        }
    }
    $form = ['course_code' => $code, 'course_name' => $name, 'description' => $desc];
    if ($action === 'update') { $edit = ['course_id' => $id]; }
} elseif (isset($_GET['edit'])) {
    $edit = $courses->find((int) $_GET['edit']);
    if ($edit) { $form = $edit; }
}

$q    = trim($_GET['q'] ?? '');
$list = $courses->all($q);

$pageTitle = 'Courses';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Courses</h1>

<div class="card">
    <h2><?= $edit ? 'Edit Course' : 'Add Course' ?></h2>
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="course_id" value="<?= (int) $edit['course_id'] ?>"><?php endif; ?>
        <label for="course_code">Course code</label>
        <input type="text" id="course_code" name="course_code" maxlength="20" value="<?= e($form['course_code']) ?>" required>
        <label for="course_name">Course name</label>
        <input type="text" id="course_name" name="course_name" maxlength="100" value="<?= e($form['course_name']) ?>" required>
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"><?= e($form['description']) ?></textarea>
        <p>
            <button type="submit"><?= $edit ? 'Update course' : 'Add course' ?></button>
            <?php if ($edit): ?><a class="btn secondary" href="courses.php">Cancel</a><?php endif; ?>
        </p>
    </form>
</div>

<form method="get" class="filters">
    <input type="text" name="q" placeholder="Search code or name" value="<?= e($q) ?>">
    <button type="submit">Search</button>
    <?php if ($q !== ''): ?><a class="btn secondary" href="courses.php">Clear</a><?php endif; ?>
</form>

<table>
    <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($list as $c): ?>
        <tr>
            <td><?= (int) $c['course_id'] ?></td>
            <td><?= e($c['course_code']) ?></td>
            <td><?= e($c['course_name']) ?></td>
            <td><?= e($c['description']) ?></td>
            <td>
                <a class="btn secondary" href="courses.php?edit=<?= (int) $c['course_id'] ?>">Edit</a>
                <form method="post" class="inline" onsubmit="return confirm('Delete this course?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="course_id" value="<?= (int) $c['course_id'] ?>">
                    <button type="submit" class="danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="5">No courses found.</td></tr><?php endif; ?>
    </tbody>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
