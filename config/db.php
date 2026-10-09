<?php
// config/db.php -- creates the single shared PDO connection
require_once __DIR__ . '/../classes/Database.php';

$dsn  = "mysql:host=localhost;dbname=training_db;charset=utf8mb4";
$user = "root";
$pass = "";

// Optional local override (git-ignored) so real passwords are never committed.
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

$db = Database::getInstance($dsn, $user, $pass);
