<?php
// admin/login.php -- generic administrator login
$base = '../';
require_once __DIR__ . '/../includes/helpers.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (hash_equals(ADMIN_USER, $u) && password_verify($p, ADMIN_PASS_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = ADMIN_USER;
        flash('success', 'Logged in.');
        redirect('courses.php');
    }
    $error = 'Invalid username or password.';
}
$pageTitle = 'Log in';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Administrator Login</h1>
<div class="card" style="max-width:420px">
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack">
        <?= csrf_field() ?>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <p><button type="submit">Log in</button></p>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
