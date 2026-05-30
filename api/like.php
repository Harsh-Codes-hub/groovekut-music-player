<?php
// ============================================================
// GrooveKut — Like Toggle
// File: api/like.php
// POST song_id → toggles liked_songs, returns new state
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

// Check if already liked
$check = $conn->query(
    "SELECT id FROM liked_songs
     WHERE user_id = $user_id AND song_id = $song_id LIMIT 1"
);

if ($check && $check->num_rows > 0) {
    // Already liked → unlike
    $conn->query(
        "DELETE FROM liked_songs
         WHERE user_id = $user_id AND song_id = $song_id"
    );
    echo json_encode(['success' => true, 'liked' => false]);
} else {
    // Not liked → like
    $conn->query(
        "INSERT INTO liked_songs (user_id, song_id, liked_at)
         VALUES ($user_id, $song_id, NOW())"
    );
    echo json_encode(['success' => true, 'liked' => true]);
}
