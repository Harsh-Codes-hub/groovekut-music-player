<?php
// ============================================================
// GrooveKut — Get Queue
// File: api/get_queue.php
// GET song_id + context (genre|mood|random) → returns queue JSON
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';
header('Content-Type: application/json');

$song_id = intval($_GET['song_id'] ?? 0);
$context = $_GET['context'] ?? 'random'; // 'genre', 'mood', 'random'
$value   = $_GET['value']   ?? '';       // genre name or mood name if context set

if (!$song_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid song_id']);
    exit;
}

// Related genres map
$related = [
    'Hip-Hop'    => ['R&B', 'Electronic'],
    'Pop'        => ['R&B', 'Indie'],
    'Electronic' => ['Hip-Hop', 'Pop'],
    'Indie'      => ['Rock', 'R&B'],
    'R&B'        => ['Hip-Hop', 'Pop'],
    'Rock'       => ['Indie', 'Electronic'],
    'Punjabi'    => ['Bollywood', 'Hip-Hop'],
    'Bollywood'  => ['Punjabi', 'Pop'],
    'Lo-fi'      => ['Electronic', 'Indie'],
];

// Get the clicked song's genre + mood
$song_res = $conn->query("SELECT genre, mood_tag FROM songs WHERE id = $song_id LIMIT 1");
if (!$song_res || $song_res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Song not found']);
    exit;
}
$song_data = $song_res->fetch_assoc();
$genre     = $song_data['genre'];
$mood      = $song_data['mood_tag'];

// Build genre pool
if ($context === 'mood' && $value) {
    $m = $conn->real_escape_string($value);
    $res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre
         FROM songs WHERE mood_tag = '$m'
         ORDER BY RAND() LIMIT 50"
    );
} elseif ($context === 'genre' && $value) {
    $g        = $conn->real_escape_string($value);
    $rel      = $related[$value] ?? [];
    $all_genres = array_merge([$value], $rel);
    $genre_list = implode("','", array_map([$conn, 'real_escape_string'], $all_genres));
    $res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre
         FROM songs WHERE genre IN ('$genre_list')
         ORDER BY RAND() LIMIT 50"
    );
} else {
    // Default: clicked song genre + related
    $rel        = $related[$genre] ?? [];
    $all_genres = array_merge([$genre], $rel);
    $genre_list = implode("','", array_map([$conn, 'real_escape_string'], $all_genres));
    $res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre
         FROM songs WHERE genre IN ('$genre_list')
         ORDER BY RAND() LIMIT 50"
    );
}

$queue = [];
// Put clicked song first
$first_res = $conn->query(
    "SELECT id, title, artist, cover_path, file_path, mood_tag, genre
     FROM songs WHERE id = $song_id LIMIT 1"
);
if ($first_res) {
    $first = $first_res->fetch_assoc();
    $queue[] = $first;
}

// Add rest, skip if it's the clicked song
while ($row = $res->fetch_assoc()) {
    if ($row['id'] != $song_id) {
        $queue[] = $row;
    }
}

echo json_encode(['success' => true, 'queue' => $queue]);
