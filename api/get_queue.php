<?php
// ============================================================
// GrooveKut — Get Queue
// File: api/get_queue.php
// GET song_id + context (genre|mood|liked|top|random) → returns queue JSON
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';
header('Content-Type: application/json');

$song_id = intval($_GET['song_id'] ?? 0);
$context = $_GET['context'] ?? 'random'; // 'genre', 'mood', 'liked', 'top', 'random'
$value   = $_GET['value']   ?? '';       // genre name, mood name, or comma-separated song IDs
$smart   = intval($_GET['smart'] ?? 1);  // 1 = smart filtered queue | 0 = all songs random
                                         // NOTE: smart param is IGNORED for locked contexts
                                         // (liked, top, genre) — they always use their own pool

if (!$song_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid song_id']);
    exit;
}

// ── Helper: fetch a song row by ID ────────────────────────────
function fetchSong($conn, $id) {
    $id  = intval($id);
    $res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE id = $id LIMIT 1"
    );
    return ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
}

// ══════════════════════════════════════════════════════════════
// LOCKED CONTEXT: Liked Songs playlist
// value = comma-separated ordered song IDs from library.php
// Queue = exactly those songs in that order, current song first
// Smart Queue button is hidden on this context — no rebuild
// ══════════════════════════════════════════════════════════════
if ($context === 'liked' && $value) {
    $raw_ids = array_filter(array_map('intval', explode(',', $value)));
    if (empty($raw_ids)) {
        echo json_encode(['success' => false, 'error' => 'No song IDs in liked playlist']);
        exit;
    }

    // Fetch all songs in one query, preserving order
    $id_list = implode(',', $raw_ids);
    $res     = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE id IN ($id_list)"
    );
    $songs_map = [];
    while ($row = $res->fetch_assoc()) {
        $songs_map[$row['id']] = $row;
    }

    // Re-order by the original liked order (most recently liked first)
    $queue = [];
    foreach ($raw_ids as $id) {
        if (isset($songs_map[$id])) $queue[] = $songs_map[$id];
    }

    // Put the clicked song first, keep the rest in order after it
    $clicked_pos = array_search($song_id, array_column($queue, 'id'));
    if ($clicked_pos !== false && $clicked_pos > 0) {
        $before = array_slice($queue, 0, $clicked_pos);
        $from   = array_slice($queue, $clicked_pos);
        $queue  = array_merge($from, $before);
    }

    echo json_encode([
        'success'         => true,
        'queue'           => $queue,
        'locked_context'  => 'liked',
        'locked_label'    => 'Liked Songs',
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// LOCKED CONTEXT: Most Played playlist
// value = comma-separated ordered song IDs from library.php
// Queue = exactly those songs in that order, current song first
// Smart Queue button is hidden on this context — no rebuild
// ══════════════════════════════════════════════════════════════
if ($context === 'top' && $value) {
    $raw_ids = array_filter(array_map('intval', explode(',', $value)));
    if (empty($raw_ids)) {
        echo json_encode(['success' => false, 'error' => 'No song IDs in top played playlist']);
        exit;
    }

    $id_list = implode(',', $raw_ids);
    $res     = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE id IN ($id_list)"
    );
    $songs_map = [];
    while ($row = $res->fetch_assoc()) {
        $songs_map[$row['id']] = $row;
    }

    // Preserve play-count order from library
    $queue = [];
    foreach ($raw_ids as $id) {
        if (isset($songs_map[$id])) $queue[] = $songs_map[$id];
    }

    // Put the clicked song first
    $clicked_pos = array_search($song_id, array_column($queue, 'id'));
    if ($clicked_pos !== false && $clicked_pos > 0) {
        $before = array_slice($queue, 0, $clicked_pos);
        $from   = array_slice($queue, $clicked_pos);
        $queue  = array_merge($from, $before);
    }

    echo json_encode([
        'success'         => true,
        'queue'           => $queue,
        'locked_context'  => 'top',
        'locked_label'    => 'Most Played',
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// LOCKED CONTEXT: Genre Playlist (user's top played in that genre)
// value = comma-separated ordered song IDs from library.php
// Queue = exactly those songs in play-count order, current song first
// Smart Queue button is hidden on this context — no rebuild
// Note: context=genre (without -playlist) still goes to smart queue below
// ══════════════════════════════════════════════════════════════
if ($context === 'genre-playlist' && $value) {
    $raw_ids = array_filter(array_map('intval', explode(',', $value)));
    if (empty($raw_ids)) {
        echo json_encode(['success' => false, 'error' => 'No song IDs in genre playlist']);
        exit;
    }

    $id_list = implode(',', $raw_ids);
    $res     = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE id IN ($id_list)"
    );
    $songs_map = [];
    while ($row = $res->fetch_assoc()) {
        $songs_map[$row['id']] = $row;
    }

    // Preserve play-count order from library (most played first)
    $queue = [];
    foreach ($raw_ids as $id) {
        if (isset($songs_map[$id])) $queue[] = $songs_map[$id];
    }

    // Put the clicked song first, keep rest in order after it
    $clicked_pos = array_search($song_id, array_column($queue, 'id'));
    if ($clicked_pos !== false && $clicked_pos > 0) {
        $before = array_slice($queue, 0, $clicked_pos);
        $from   = array_slice($queue, $clicked_pos);
        $queue  = array_merge($from, $before);
    }

    $label = $_GET['label'] ?? 'Genre';
    echo json_encode([
        'success'        => true,
        'queue'          => $queue,
        'locked_context' => 'genre-playlist',
        'locked_label'   => $label . ' Playlist',
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// SMART QUEUE OFF — all songs, pure random
// ══════════════════════════════════════════════════════════════
if ($smart === 0) {
    $queue     = [];
    $first_res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE id = $song_id LIMIT 1"
    );
    if ($first_res) $queue[] = $first_res->fetch_assoc();
    $res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE id != $song_id ORDER BY RAND() LIMIT 101"
    );
    while ($row = $res->fetch_assoc()) $queue[] = $row;
    echo json_encode(['success' => true, 'queue' => $queue]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// SMART QUEUE ON — genre / mood / default (related genres)
// ══════════════════════════════════════════════════════════════

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
$song_res = $conn->query(
    "SELECT genre, mood_tag FROM songs WHERE id = $song_id LIMIT 1"
);
if (!$song_res || $song_res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Song not found']);
    exit;
}
$song_data = $song_res->fetch_assoc();
$genre     = $song_data['genre'];
$mood      = $song_data['mood_tag'];

// Build genre pool
if ($context === 'mood' && $value) {
    $m   = $conn->real_escape_string($value);
    $res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE mood_tag = '$m'
         ORDER BY RAND() LIMIT 50"
    );
} elseif ($context === 'genre' && $value) {
    $rel        = $related[$value] ?? [];
    $all_genres = array_merge([$value], $rel);
    $genre_list = implode("','", array_map([$conn, 'real_escape_string'], $all_genres));
    $res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE genre IN ('$genre_list')
         ORDER BY RAND() LIMIT 50"
    );
} else {
    // Default: clicked song's genre + related genres
    $rel        = $related[$genre] ?? [];
    $all_genres = array_merge([$genre], $rel);
    $genre_list = implode("','", array_map([$conn, 'real_escape_string'], $all_genres));
    $res = $conn->query(
        "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
         FROM songs WHERE genre IN ('$genre_list')
         ORDER BY RAND() LIMIT 50"
    );
}

$queue = [];
// Put clicked song first
$first_res = $conn->query(
    "SELECT id, title, artist, cover_path, file_path, mood_tag, genre, duration, is_explicit
     FROM songs WHERE id = $song_id LIMIT 1"
);
if ($first_res) {
    $queue[] = $first_res->fetch_assoc();
}

// Add rest, skip if it's the clicked song
while ($row = $res->fetch_assoc()) {
    if ($row['id'] != $song_id) {
        $queue[] = $row;
    }
}

echo json_encode(['success' => true, 'queue' => $queue]);