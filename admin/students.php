<?php
// admin/students.php -- Student recording form (multi-table, transactional)
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_login($base);

$repo      = new EnrollmentRepository($db);
$classes   = new ClassSection($db);
$errors    = [];
$full_name = $email = $phone = '';
$class_id  = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $class_id  = (int) ($_POST['class_id'] ?? 0);

    if ($full_name === '' || strlen($full_name) > 100) { $errors[] = 'Full name is required (max 100 chars).'; }
    if ($email !== '' && (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+()\s-]{5,30}$/', $phone)) {
        $errors[] = 'Phone may contain digits, spaces, + ( ) - only (5-30 chars).';
    }
    if ($class_id <= 0 || !$classes->find($class_id)) { $errors[] = 'Please choose a valid class.'; }

    if (!$errors) {
        try {
            $new_id = $repo->recordStudent($full_name, $email, $phone, $class_id);
            flash('success', "Student recorded (ID $new_id) and enrolled successfully. One slot has been used.");
            redirect('students.php');
        } catch (NoSlotsException $ex) {
            $errors[] = 'No slots available in the selected class. Nothing was saved (transaction rolled back).';
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }
}

$classList = $classes->allWithCourse();
$pageTitle = 'Record Student';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Record Student</h1>
<div class="card" style="max-width:640px">
    <?php if ($errors): ?>
        <ul class="errors"><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <form method="post" class="stack">
        <?= csrf_field() ?>
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" maxlength="100" value="<?= e($full_name) ?>" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" maxlength="100" value="<?= e($email) ?>">
        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" maxlength="30" value="<?= e($phone) ?>">
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
        <p><button type="submit">Record &amp; enroll</button></p>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
