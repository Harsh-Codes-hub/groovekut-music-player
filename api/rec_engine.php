<?php
// ============================================================
// GrooveKut — Recommendation Engine
// File: api/rec_engine.php
// GET → returns top 20 scored songs for the current user
//
// Scoring weights:
//   play_count  × 1.0   (play history signal)
//   liked       × 3.0   (strongest signal)
//   mood match  × 2.0   (current session mood vs song mood_tag)
//   genre match × 1.5   (user preferred_genres vs song genre)
//
// Excludes songs the user has liked (already in Liked Songs)
// so recommendations feel fresh. Pass ?include_liked=1 to skip.
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id       = current_user_id();
$current_mood  = current_mood();
$mood_esc      = $conn->real_escape_string($current_mood);
$limit         = max(1, min(50, intval($_GET['limit'] ?? 20)));
$include_liked = intval($_GET['include_liked'] ?? 0);

// ── Resolve user's preferred genres ──────────────────────────
$genres_json = null;
$res = $conn->query("SELECT preferred_genres FROM users WHERE id = $user_id LIMIT 1");
if ($res) {
    $row = $res->fetch_assoc();
    $genres_json = $row['preferred_genres'];
}
$preferred_genres = $genres_json ? json_decode($genres_json, true) : [];

// Build a safe SQL IN list for genre matching
// If user has no preferred genres, genre bonus never fires (score stays 0)
$genre_in_sql = '';
if (!empty($preferred_genres)) {
    $escaped = array_map([$conn, 'real_escape_string'], $preferred_genres);
    $genre_in_sql = "'" . implode("','", $escaped) . "'";
}

// ── Main scoring query ────────────────────────────────────────
// Left joins bring in per-user signals. COALESCE handles nulls.
// Genre match uses a conditional: 1 if song genre in preferred list, else 0.
// Mood match: 1 if song mood_tag = current session mood, else 0.

$genre_case = $genre_in_sql
    ? "CASE WHEN s.genre IN ($genre_in_sql) THEN 1 ELSE 0 END"
    : "0";

$liked_exclude = $include_liked ? '' : "AND ls.id IS NULL";

$sql = "
    SELECT
        s.id,
        s.title,
        s.artist,
        s.genre,
        s.mood_tag,
        s.cover_path,
        s.file_path,
        s.duration,
        COALESCE(ph.play_count, 0)                              AS play_count,
        IF(ls.id IS NOT NULL, 1, 0)                             AS is_liked,
        IF(s.mood_tag = '$mood_esc', 1, 0)                      AS mood_match,
        ($genre_case)                                           AS genre_match,

        -- Final weighted score
        (
            COALESCE(ph.play_count, 0) * 1.0
          + IF(ls.id IS NOT NULL, 3.0, 0)
          + IF(s.mood_tag = '$mood_esc', 2.0, 0)
          + ($genre_case) * 1.5
        ) AS rec_score

    FROM songs s

    -- Play history for this user (may be NULL = never played)
    LEFT JOIN play_history ph
        ON ph.song_id = s.id AND ph.user_id = $user_id

    -- Liked songs for this user (may be NULL = not liked)
    LEFT JOIN liked_songs ls
        ON ls.song_id = s.id AND ls.user_id = $user_id

    WHERE 1=1
    $liked_exclude

    ORDER BY rec_score DESC, RAND()
    LIMIT $limit
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . $conn->error]);
    exit;
}

$songs = [];
while ($row = $result->fetch_assoc()) {
    // Cast numerics cleanly for JS
    $row['id']          = (int)$row['id'];
    $row['play_count']  = (int)$row['play_count'];
    $row['is_liked']    = (int)$row['is_liked'];
    $row['mood_match']  = (int)$row['mood_match'];
    $row['genre_match'] = (int)$row['genre_match'];
    $row['rec_score']   = (float)$row['rec_score'];
    $row['duration']    = $row['duration'] ? (int)$row['duration'] : null;
    $songs[] = $row;
}

echo json_encode([
    'success' => true,
    'mood'    => $current_mood,
    'count'   => count($songs),
    'songs'   => $songs,
]);
