<?php
// admin/enrollments.php -- Enrollment list (JOINs) with search, filter, sort, pagination, cancel
$base = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/Course.php';
require_login($base);

$repo = new EnrollmentRepository($db);

// POST: cancel an enrollment (transaction), then redirect back (keeps filters)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['enrollment_id'] ?? 0);
    try {
        $repo->cancel($id);
        flash('success', 'Enrollment cancelled and the slot was restored.');
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
    }
    redirect('enrollments.php' . (!empty($_POST['return']) ? '?' . ltrim($_POST['return'], '?') : ''));
}

// GET: filters
$q         = trim($_GET['q'] ?? '');
$status    = in_array($_GET['status'] ?? '', ['active', 'cancelled'], true) ? $_GET['status'] : '';
$course_id = (int) ($_GET['course_id'] ?? 0);
$sort      = in_array($_GET['sort'] ?? '', ['date', 'student', 'course', 'class', 'status'], true) ? $_GET['sort'] : 'date';
$dir       = ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$page      = max(1, (int) ($_GET['page'] ?? 1));
$perPage   = 10;

$filters = ['q' => $q, 'status' => $status, 'course_id' => $course_id, 'sort' => $sort, 'dir' => $dir];
$total   = $repo->countWithDetails($filters);
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min($page, $pages);
$rows    = $repo->allWithDetails($filters, $page, $perPage);

$courseList = (new Course($db))->all();
$qs = array_filter(['q' => $q, 'status' => $status, 'course_id' => $course_id ?: '', 'sort' => $sort, 'dir' => $dir],
                   function ($v) { return $v !== ''; });
$return = http_build_query($qs + ['page' => $page]);

// sortable header link
function sort_link($label, $key, $sort, $dir, array $qs) {
    $newDir = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
    $arrow  = $sort === $key ? ($dir === 'asc' ? ' &#9650;' : ' &#9660;') : '';
    return '<a href="?' . e(http_build_query(array_merge($qs, ['sort' => $key, 'dir' => $newDir]))) . '">' . e($label) . '</a>' . $arrow;
}

$pageTitle = 'Enrollments';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Enrollments</h1>

<form method="get" class="filters">
    <input type="text" name="q" placeholder="Search student, email, class, course" value="<?= e($q) ?>">
    <select name="status">
        <option value="">All statuses</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>
    <select name="course_id">
        <option value="">All courses</option>
        <?php foreach ($courseList as $c): ?>
            <option value="<?= (int) $c['course_id'] ?>" <?= $course_id === (int) $c['course_id'] ? 'selected' : '' ?>><?= e($c['course_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filter</button>
    <a class="btn secondary" href="enrollments.php">Reset</a>
</form>

<p><?= (int) $total ?> record(s) found.</p>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th><?= sort_link('Student', 'student', $sort, $dir, $qs) ?></th>
        <th><?= sort_link('Course', 'course', $sort, $dir, $qs) ?></th>
        <th><?= sort_link('Class', 'class', $sort, $dir, $qs) ?></th>
        <th>Schedule</th><th>Instructor</th>
        <th><?= sort_link('Enrolled', 'date', $sort, $dir, $qs) ?></th>
        <th><?= sort_link('Status', 'status', $sort, $dir, $qs) ?></th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= (int) $r['enrollment_id'] ?></td>
            <td><?= e($r['full_name']) ?><br><small><?= e($r['email']) ?></small></td>
            <td><?= e($r['course_name']) ?></td>
            <td><?= e($r['class_code']) ?></td>
            <td><?= e($r['schedule']) ?></td>
            <td><?= e($r['instructor']) ?></td>
            <td><?= e($r['enrollment_date']) ?></td>
            <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
            <td>
                <?php if ($r['status'] === 'active'): ?>
                    <form method="post" class="inline" onsubmit="return confirm('Cancel this enrollment?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="enrollment_id" value="<?= (int) $r['enrollment_id'] ?>">
                        <input type="hidden" name="return" value="<?= e($return) ?>">
                        <button type="submit" class="danger">Cancel</button>
                    </form>
                <?php else: ?>&mdash;<?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="9">No enrollments found.</td></tr><?php endif; ?>
    </tbody>
</table>

<?php if ($pages > 1): ?>
<p class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
        <?php else: ?><a href="<?= e(page_url($qs, $i)) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
</p>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
