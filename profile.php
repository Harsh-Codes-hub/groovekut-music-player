<?php
// ============================================================
// GrooveKut — Profile
// File: profile.php
// ============================================================

require_once 'includes/session_helper.php';
require_login();

$user_id  = current_user_id();
$username = $_SESSION['username'];
$mood     = current_mood();

// ── Stats ─────────────────────────────────────────────────────
// Total plays
$res = $conn->query("SELECT SUM(play_count) AS total FROM play_history WHERE user_id = $user_id");
$total_plays = (int)($res->fetch_assoc()['total'] ?? 0);

// Total liked
$res = $conn->query("SELECT COUNT(*) AS total FROM liked_songs WHERE user_id = $user_id");
$total_liked = (int)($res->fetch_assoc()['total'] ?? 0);

// Total unique songs played
$res = $conn->query("SELECT COUNT(*) AS total FROM play_history WHERE user_id = $user_id");
$unique_songs = (int)($res->fetch_assoc()['total'] ?? 0);

// Fav genre
$fav_genre = null;
$res = $conn->query(
    "SELECT s.genre, SUM(ph.play_count) AS total
     FROM play_history ph
     JOIN songs s ON s.id = ph.song_id
     WHERE ph.user_id = $user_id
     GROUP BY s.genre
     ORDER BY total DESC
     LIMIT 1"
);
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $fav_genre = $row['genre'];
}

// Fav mood (most logged)
$fav_mood = null;
$res = $conn->query(
    "SELECT mood, COUNT(*) AS total
     FROM mood_log
     WHERE user_id = $user_id
     GROUP BY mood
     ORDER BY total DESC
     LIMIT 1"
);
if ($res && $res->num_rows > 0) {
    $fav_mood = $res->fetch_assoc()['mood'];
}

// Member since
$res = $conn->query("SELECT created_at FROM users WHERE id = $user_id LIMIT 1");
$created_at = $res->fetch_assoc()['created_at'] ?? null;
$member_since = $created_at ? date('M Y', strtotime($created_at)) : 'Unknown';

// Recent play history (last 20)
$history = [];
$res = $conn->query(
    "SELECT s.id, s.title, s.artist, s.cover_path, s.genre,
            s.mood_tag, s.is_explicit, ph.play_count, ph.played_at
     FROM play_history ph
     JOIN songs s ON s.id = ph.song_id
     WHERE ph.user_id = $user_id
     ORDER BY ph.played_at DESC
     LIMIT 20"
);
while ($row = $res->fetch_assoc()) $history[] = $row;

// Preferred genres from onboarding
$pref_genres = [];
$res = $conn->query("SELECT preferred_genres FROM users WHERE id = $user_id LIMIT 1");
$row = $res->fetch_assoc();
if ($row && $row['preferred_genres']) {
    $pref_genres = json_decode($row['preferred_genres'], true) ?? [];
}

$mood_emojis = ['happy'=>'😄','chill'=>'😌','focus'=>'🎯','sad'=>'🌧️','hype'=>'🔥'];

$page_title = 'Profile';
require_once 'includes/header.php';
?>

<div class="profile-page">

  <!-- ── Profile hero ────────────────────────────────────── -->
  <div class="profile-hero">
    <div class="profile-avatar">
      <?= strtoupper(substr($username, 0, 1)) ?>
    </div>
    <div class="profile-meta">
      <h1 class="profile-username"><?= htmlspecialchars($username) ?></h1>
      <p class="profile-since">Member since <?= $member_since ?></p>
      <div class="profile-badges">
        <?php if ($fav_genre): ?>
          <span class="badge badge-genre">
            <i class="ri-music-2-line"></i> <?= htmlspecialchars($fav_genre) ?> Fan
          </span>
        <?php endif; ?>
        <?php if ($fav_mood): ?>
          <span class="badge badge-mood mood-<?= $fav_mood ?>">
            <?= $mood_emojis[$fav_mood] ?> <?= ucfirst($fav_mood) ?> Listener
          </span>
        <?php endif; ?>
        <?php if ($total_plays >= 100): ?>
          <span class="badge badge-milestone">
            <i class="ri-headphone-line"></i> 100+ Plays
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── Stats row ───────────────────────────────────────── -->
  <div class="stats-row">
    <div class="stat-card">
      <span class="stat-num"><?= number_format($total_plays) ?></span>
      <span class="stat-label">Total Plays</span>
    </div>
    <div class="stat-card">
      <span class="stat-num"><?= $unique_songs ?></span>
      <span class="stat-label">Songs Played</span>
    </div>
    <div class="stat-card">
      <span class="stat-num"><?= $total_liked ?></span>
      <span class="stat-label">Liked Songs</span>
    </div>
    <div class="stat-card">
      <span class="stat-num"><?= $fav_genre ?? '—' ?></span>
      <span class="stat-label">Top Genre</span>
    </div>
  </div>

  <!-- ── Preferred genres ────────────────────────────────── -->
  <?php if (!empty($pref_genres)): ?>
    <div class="profile-section">
      <h2 class="section-title">Your Genres</h2>
      <div class="genre-browse-row">
        <?php foreach ($pref_genres as $g): ?>
          <a href="/groovekut/search.php?genre=<?= urlencode($g) ?>"
             class="genre-browse-card active-genre">
            <?= htmlspecialchars($g) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- ── Recent history ──────────────────────────────────── -->
  <div class="profile-section">
    <div class="section-header-row">
      <h2 class="section-title">Recently Played</h2>
      <a href="/groovekut/library.php" class="section-link">
        View Library <i class="ri-arrow-right-line"></i>
      </a>
    </div>

    <?php if (empty($history)): ?>
      <div class="lib-empty">
        <p>No history yet. Start listening!</p>
        <a href="/groovekut/dashboard.php" class="btn-primary">Go to Home</a>
      </div>
    <?php else: ?>
      <div class="lib-song-list">
        <?php foreach ($history as $i => $s): ?>
          <a href="/groovekut/player.php?id=<?= $s['id'] ?>" class="lib-row">
            <span class="lib-row-num"><?= $i + 1 ?></span>
            <div class="lib-row-cover">
              <?php if ($s['cover_path']): ?>
                <img src="/groovekut/<?= htmlspecialchars($s['cover_path']) ?>" alt="cover"/>
              <?php else: ?>
                <div class="cover-placeholder sm">🎵</div>
              <?php endif; ?>
              <div class="lib-play-overlay"><i class="ri-play-fill"></i></div>
            </div>
            <div class="lib-row-info">
              <p class="lib-row-title">
                <?= htmlspecialchars($s['title']) ?>
                <?php if ($s['is_explicit']): ?><span class="tag-explicit sm">E</span><?php endif; ?>
              </p>
              <p class="lib-row-artist"><?= htmlspecialchars($s['artist']) ?></p>
            </div>
            <span class="lib-row-genre"><?= htmlspecialchars($s['genre']) ?></span>
            <span class="lib-row-plays"><?= $s['play_count'] ?>× played</span>
            <span class="lib-row-time">
              <?= date('d M', strtotime($s['played_at'])) ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
