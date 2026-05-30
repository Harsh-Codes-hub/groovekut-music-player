<?php
// ============================================================
// GrooveKut — Logout
// File: auth/logout.php
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';

// Clear remember_me token from DB
if (is_logged_in()) {
    $id = current_user_id();
    $conn->query("UPDATE users SET remember_token = NULL WHERE id = $id");
}

// Destroy cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy session
session_destroy();

header('Location: /groovekut/auth/login.php');
exit;
