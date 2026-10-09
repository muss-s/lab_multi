<?php
// admin/logout.php
require_once __DIR__ . '/../includes/helpers.php';
$_SESSION = [];
session_destroy();
redirect('login.php');
