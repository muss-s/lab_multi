<?php
// includes/header.php -- set $base ('' or '../') and optionally $pageTitle first.
require_once __DIR__ . '/helpers.php';
$base      = $base ?? '';
$pageTitle = $pageTitle ?? 'Training Enrollment System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Training Enrollment System</title>
    <link rel="stylesheet" href="<?= e($base) ?>css/style.css?v=<?= (int) @filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body>
<div class="menubar">
    <span class="brand">&#127891; Training Enrollment</span>
    <a href="<?= e($base) ?>index.php">Home</a>
    <a href="<?= e($base) ?>admin/courses.php">Courses</a>
    <a href="<?= e($base) ?>admin/classes.php">Classes</a>
    <a href="<?= e($base) ?>admin/enrollments.php">Enrollments</a>
    <span class="spacer"></span>
    <?php if (!empty($_SESSION['admin'])): ?>
        <a href="<?= e($base) ?>admin/logout.php">Log out (<?= e($_SESSION['admin']) ?>)</a>
    <?php else: ?>
        <a href="<?= e($base) ?>admin/login.php">Log in</a>
    <?php endif; ?>
    <span class="clock" id="clock"></span>
</div>
<div class="window">
    <div class="titlebar">
        <span class="dots"><i></i><i></i><i></i></span>
        <span class="wtitle"><?= e($pageTitle) ?></span>
    </div>
    <main class="content">
<?= flash() ?>
