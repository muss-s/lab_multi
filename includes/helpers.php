<?php
// includes/helpers.php -- small shared helpers (session, escaping, CSRF, flash)
require_once __DIR__ . '/../config/app.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function redirect($url) { header('Location: ' . $url); exit; }

function csrf_token() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid or missing CSRF token.');
    }
}

// flash('success', 'msg') stores; flash() returns HTML once and clears it.
function flash($type = null, $msg = null) {
    if ($type !== null) { $_SESSION['flash'] = [$type, $msg]; return ''; }
    if (!empty($_SESSION['flash'])) {
        list($t, $m) = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return '<div class="alert ' . e($t) . '">' . e($m) . '</div>';
    }
    return '';
}

function require_login($base) {
    if (empty($_SESSION['admin'])) { redirect($base . 'admin/login.php'); }
}

function page_url(array $params, $page) {
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
