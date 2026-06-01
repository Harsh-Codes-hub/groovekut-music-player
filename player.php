<?php
// ============================================================
// GrooveKut — Player
// File: player.php
// ============================================================

require_once 'includes/session_helper.php';

$song_id = intval($_GET['id'] ?? 0);
$context = $_GET['context'] ?? 'random';
$value   = $_GET['value']   ?? '';

if (!$song_id) {
    header('Location: /groovekut/dashboard.php');
    exit;
}

// Fetch song data
$res = $conn->query(
    "SELECT id, title, artist, genre, mood_tag, file_path, cover_path, duration, is_explicit
     FROM songs WHERE id = $song_id LIMIT 1"
);

if (!$res || $res->num_rows === 0) {
    header('Location: /groovekut/dashboard.php');
    exit;
}

$song = $res->fetch_assoc();

// Check if liked (only for logged in users)
$is_liked = false;
if (is_logged_in()) {
    $uid      = current_user_id();
    $like_res = $conn->query(
        "SELECT id FROM liked_songs
         WHERE user_id = $uid AND song_id = $song_id LIMIT 1"
    );
    $is_liked = ($like_res && $like_res->num_rows > 0);
}

// Mood gradient map
$mood_gradients = [
    'happy' => '180, 120, 40',
    'chill' => '30,  80,  160',
    'hype'  => '180, 40,  30',
    'sad'   => '80,  40,  140',
    'focus' => '20,  120, 120',
];
$mood_rgb = $mood_gradients[$song['mood_tag']] ?? '55, 138, 221';

// Determine if this is a locked playlist context.
// Locked contexts: 'liked', 'top', 'genre'
// These contexts play only songs from that specific playlist.
// The smart queue toggle is hidden and replaced with a playlist badge.
$locked_contexts = ['liked', 'top', 'genre-playlist'];
$is_locked       = in_array($context, $locked_contexts);

// For genre-playlist, the genre name comes via ?label= param (value holds song IDs)
$genre_label = htmlspecialchars($_GET['label'] ?? '');

// Human-readable label for the locked playlist badge
$locked_labels = [
    'liked'          => '❤️ Liked Songs',
    'top'            => '🔥 Most Played',
    'genre-playlist' => '🎵 ' . ($genre_label ?: 'Genre') . ' Playlist',
];
$locked_label = $is_locked ? ($locked_labels[$context] ?? 'Playlist') : '';

$duration_fmt = '';
if ($song['duration']) {
    $m = floor($song['duration'] / 60);
    $s = $song['duration'] % 60;
    $duration_fmt = $m . ':' . str_pad($s, 2, '0', STR_PAD_LEFT);
}

$page_title = $song['title'] . ' — ' . $song['artist'];
require_once 'includes/header.php';
?>

<!-- Player background layers -->
<div class="player-bg" id="player-bg"
     style="--mood-r: <?= $mood_rgb ?>;"></div>
<canvas id="visualizer-canvas"></canvas>

<!-- Hidden canvas for color extraction -->
<?php if ($song['cover_path']): ?>
<canvas id="color-extractor" style="display:none;" width="50" height="50"></canvas>
<?php endif; ?>

<div class="player-wrap">

  <!-- ── Left: Cover + Info ─────────────────────────────── -->
  <div class="player-left">

    <div class="player-cover-wrap">
      <?php if ($song['cover_path']): ?>
        <img id="player-cover-img"
             src="/groovekut/<?= htmlspecialchars($song['cover_path']) ?>"
             alt="<?= htmlspecialchars($song['title']) ?>"
             class="player-cover"
             crossorigin="anonymous"/>
      <?php else: ?>
        <div class="player-cover player-cover-placeholder">
          <i class="ri-music-2-line"></i>
        </div>
      <?php endif; ?>
    </div>

    <div class="player-meta">
      <h1 class="player-title" id="player-title">
        <?= htmlspecialchars($song['title']) ?>
      </h1>
      <p class="player-artist" id="player-artist">
        <?= htmlspecialchars($song['artist']) ?>
      </p>
      <div class="player-tags">
        <span class="tag-genre" id="player-genre"><?= htmlspecialchars($song['genre']) ?></span>
        <span class="tag-mood mood-<?= $song['mood_tag'] ?>" id="player-mood"><?= ucfirst($song['mood_tag']) ?></span>
        <?php if ($song['is_explicit']): ?>
          <span class="tag-explicit">E</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Like button -->
    <button class="like-btn <?= $is_liked ? 'liked' : '' ?>"
            id="like-btn"
            data-song-id="<?= $song_id ?>"
            title="<?= $is_liked ? 'Unlike' : 'Like' ?>">
      <i class="<?= $is_liked ? 'ri-heart-3-fill' : 'ri-heart-3-line' ?>"></i>
    </button>

  </div>

  <!-- ── Center: Controls ───────────────────────────────── -->
  <div class="player-center">

    <!-- Progress bar -->
    <div class="progress-wrap">
      <span class="time-label" id="time-current">0:00</span>
      <div class="progress-bar-track" id="progress-track">
        <div class="progress-bar-fill" id="progress-fill"></div>
        <div class="progress-thumb" id="progress-thumb"></div>
      </div>
      <span class="time-label" id="time-total"><?= $duration_fmt ?: '0:00' ?></span>
    </div>

    <!-- Main controls -->
    <div class="controls-row">

      <!-- Shuffle -->
      <button class="ctrl-btn ctrl-toggle" id="shuffle-btn" title="Shuffle">
        <i class="ri-shuffle-line"></i>
      </button>

      <!-- Previous -->
      <button class="ctrl-btn ctrl-prev" id="prev-btn" title="Previous">
        <i class="ri-skip-back-fill"></i>
      </button>

      <!-- Play / Pause -->
      <button class="ctrl-btn ctrl-play" id="play-btn" title="Play">
        <i class="ri-play-fill" id="play-icon"></i>
      </button>

      <!-- Next -->
      <button class="ctrl-btn ctrl-next" id="next-btn" title="Next">
        <i class="ri-skip-forward-fill"></i>
      </button>

      <!-- Loop -->
      <button class="ctrl-btn ctrl-toggle" id="loop-btn" title="Loop Off">
        <i class="ri-repeat-line"></i>
      </button>

    </div>

    <!-- Secondary controls row -->
    <div class="controls-secondary">

      <!-- Volume -->
      <div class="volume-wrap">
        <button class="ctrl-sm-btn" id="mute-btn" title="Mute">
          <i class="ri-volume-up-line" id="volume-icon"></i>
        </button>
        <input type="range" id="volume-slider" class="volume-slider"
               min="0" max="100" value="80"/>
      </div>

      <!-- Autoplay -->
      <button class="ctrl-sm-btn ctrl-toggle active" id="autoplay-btn" title="Autoplay On">
        <i class="ri-play-list-2-line"></i>
        <span class="ctrl-label" id="autoplay-label">Autoplay</span>
      </button>

      <?php if ($is_locked): ?>
        <!-- Locked playlist badge — replaces smart queue button -->
        <!-- JS also reads LOCKED_CONTEXT so it never calls buildQueue with smart toggle -->
        <div class="ctrl-sm-btn playlist-lock-badge" id="smart-queue-btn" title="Playing from: <?= $locked_label ?>">
          <i class="ri-lock-line"></i>
          <span class="ctrl-label" id="smart-queue-label"><?= $locked_label ?></span>
        </div>
      <?php else: ?>
        <!-- Smart Queue — only shown for non-locked contexts (home, search, dashboard) -->
        <button class="ctrl-sm-btn ctrl-toggle active" id="smart-queue-btn" title="Smart Queue On">
          <i class="ri-sparkling-line"></i>
          <span class="ctrl-label" id="smart-queue-label">Smart</span>
        </button>
      <?php endif; ?>

    </div>

  </div>

  <!-- ── Right: Queue ───────────────────────────────────── -->
  <div class="player-right">
    <h3 class="queue-title">
      <i class="ri-list-check"></i> Up Next
      <?php if ($is_locked): ?>
        <span class="queue-context-tag"><?= $locked_label ?></span>
      <?php endif; ?>
    </h3>
    <div class="queue-list" id="queue-list">
      <p class="queue-loading">Building queue...</p>
    </div>
  </div>

</div>

<!-- Hidden audio element -->
<audio id="groovekut-audio"
       src="/groovekut/<?= htmlspecialchars($song['file_path']) ?>"
       preload="auto"></audio>

<script>
  // Pass PHP data to JS
  const INITIAL_SONG = {
    id:        <?= $song_id ?>,
    title:     <?= json_encode($song['title']) ?>,
    artist:    <?= json_encode($song['artist']) ?>,
    genre:     <?= json_encode($song['genre']) ?>,
    mood:      <?= json_encode($song['mood_tag']) ?>,
    file_path: <?= json_encode($song['file_path']) ?>,
    cover_path:<?= json_encode($song['cover_path'] ?? '') ?>,
    duration:  <?= intval($song['duration'] ?? 0) ?>,
    liked:     <?= $is_liked ? 'true' : 'false' ?>
  };
  const QUEUE_CONTEXT  = <?= json_encode($context) ?>;
  const QUEUE_VALUE    = <?= json_encode($value) ?>;
  const QUEUE_LABEL    = <?= json_encode($genre_label) ?>;
  const IS_LOGGED_IN   = <?= is_logged_in() ? 'true' : 'false' ?>;
  // LOCKED_CONTEXT: true when coming from a library playlist.
  // player.js uses this to disable smart queue toggle + skip rebuild.
  const LOCKED_CONTEXT = <?= $is_locked ? 'true' : 'false' ?>;
</script>
<script src="/groovekut/assets/js/player.js"></script>

<?php require_once 'includes/footer.php'; ?>