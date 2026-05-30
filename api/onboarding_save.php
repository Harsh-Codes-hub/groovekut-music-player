<?php
// ============================================================
// GrooveKut — Save Onboarding Genres
// File: api/onboarding_save.php
// Called via fetch() POST from dashboard onboarding modal
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = current_user_id();
$genres  = $_POST['genres'] ?? [];   // array of genre strings

// Sanitize — only allow known genres
$allowed = ['Pop','Hip-Hop','Lo-fi','Electronic','Indie','R&B','Rock','Classical','Punjabi','Bollywood'];
$genres  = array_values(array_intersect($genres, $allowed));

// Store as JSON, mark onboarding done
$genres_json = $conn->real_escape_string(json_encode($genres));

$conn->query(
    "UPDATE users
     SET preferred_genres = '$genres_json', onboarding_done = 1
     WHERE id = $user_id"
);

// Update session too so needs_onboarding() is immediately false
$_SESSION['onboarding_done'] = 1;

echo json_encode(['success' => true, 'genres' => $genres]);
exit;
