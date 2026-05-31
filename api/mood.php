<?php
// ============================================================
// GrooveKut — Mood API
// File: api/mood.php
// POST mood → logs to mood_log, updates session + users table
// GET        → returns current mood from session
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';
header('Content-Type: application/json');

$valid_moods = ['happy', 'chill', 'focus', 'sad', 'hype'];

// ── GET — return current mood ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'mood'    => current_mood(),
    ]);
    exit;
}

// ── POST — set new mood ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$mood = trim($_POST['mood'] ?? '');

if (!in_array($mood, $valid_moods, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid mood value']);
    exit;
}

$user_id = current_user_id();
$mood_esc = $conn->real_escape_string($mood);

// 1. Log to mood_log (append-only history)
$conn->query(
    "INSERT INTO mood_log (user_id, mood, logged_at)
     VALUES ($user_id, '$mood_esc', NOW())"
);

// 2. Update users.preferred_mood so cookie auto-login restores it
$conn->query(
    "UPDATE users SET preferred_mood = '$mood_esc' WHERE id = $user_id"
);

// 3. Update session so current_mood() returns fresh value immediately
$_SESSION['current_mood'] = $mood;

echo json_encode([
    'success' => true,
    'mood'    => $mood,
]);
