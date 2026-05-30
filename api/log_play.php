<?php
// ============================================================
// GrooveKut — Log Play
// File: api/log_play.php
// Called via POST when a song starts playing
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = current_user_id();
$song_id = intval($_POST['song_id'] ?? 0);

if (!$song_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid song_id']);
    exit;
}

// Verify song exists
$check = $conn->query("SELECT id FROM songs WHERE id = $song_id LIMIT 1");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Song not found']);
    exit;
}

// Insert or increment play_count
$conn->query(
    "INSERT INTO play_history (user_id, song_id, play_count, played_at)
     VALUES ($user_id, $song_id, 1, NOW())
     ON DUPLICATE KEY UPDATE
       play_count = play_count + 1,
       played_at  = NOW()"
);

echo json_encode(['success' => true]);
