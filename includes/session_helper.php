<?php
// ============================================================
// GrooveKut — Session Helper
// File: includes/session_helper.php
// require_once this at the top of any page that needs auth
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

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

// ── HELPERS ──────────────────────────────────────────────────

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /groovekut/auth/login.php');
        exit;
    }
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_mood(): string {
    return $_SESSION['current_mood'] ?? 'chill';
}

// Returns true if the onboarding modal should show
function needs_onboarding(): bool {
    return isset($_SESSION['onboarding_done']) && $_SESSION['onboarding_done'] == 0;
}
