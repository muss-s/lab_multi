<?php // includes/footer.php  (expects $base from the page) $base = $base ?? ''; ?>
    </main>
</div>
<footer>&copy; <?php echo date('Y'); ?> IPT - Training Enrollment</footer>
<div class="dock">
    <a href="<?= e($base) ?>index.php" data-label="Home">&#127968;</a>
    <a href="<?= e($base) ?>admin/courses.php" data-label="Courses">&#128218;</a>
    <a href="<?= e($base) ?>admin/classes.php" data-label="Classes">&#128197;</a>
    <a href="<?= e($base) ?>admin/students.php" data-label="Record Student">&#129489;&#8205;&#127891;</a>
    <a href="<?= e($base) ?>admin/enroll.php" data-label="Enroll">&#9989;</a>
    <a href="<?= e($base) ?>admin/enrollments.php" data-label="Enrollments">&#128203;</a>
    <a href="<?= e($base) ?>admin/reports.php" data-label="Reports">&#128202;</a>
</div>
<script>
(function(){var c=document.getElementById('clock');function t(){c.textContent=new Date().toLocaleString([], {weekday:'short',hour:'numeric',minute:'2-digit'});}t();setInterval(t,30000);})();
</script>
</body>
</html>
