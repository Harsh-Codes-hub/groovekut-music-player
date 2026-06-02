<?php
// ============================================================
// GrooveKut — Entry Point
// File: index.php
// Logic: cookie check → session restore → redirect
// ============================================================

session_start();
require_once 'includes/db_connect.php';

// ── COOKIE AUTO-LOGIN ────────────────────────────────────────
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {

    $token  = $conn->real_escape_string($_COOKIE['remember_token']);
    $result = $conn->query(
        "SELECT id, username, preferred_mood, preferred_genres, onboarding_done
         FROM users WHERE remember_token = '$token' LIMIT 1"
    );

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id']         = $user['id'];
        $_SESSION['username']        = $user['username'];
        $_SESSION['current_mood']    = $user['preferred_mood'] ?? 'chill';
        $_SESSION['onboarding_done'] = $user['onboarding_done'];
    }
}

// ── REDIRECT ─────────────────────────────────────────────────
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: dashboard.php');
}
exit;
