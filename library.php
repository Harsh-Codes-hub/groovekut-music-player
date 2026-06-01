<?php
// ============================================================
// GrooveKut — Library
// File: library.php
// 3 sections: Liked Songs | Top Genre Playlists | Top Played
// ============================================================

require_once 'includes/session_helper.php';
require_login();

$user_id = current_user_id();

// ── Liked Songs ───────────────────────────────────────────────
$liked_songs = [];
$res = $conn->query(
    "SELECT s.id, s.title, s.artist, s.cover_path, s.genre,
            s.mood_tag, s.duration, s.is_explicit, ls.liked_at
     FROM liked_songs ls
     JOIN songs s ON s.id = ls.song_id
     WHERE ls.user_id = $user_id
     ORDER BY ls.liked_at DESC"
);
while ($row = $res->fetch_assoc()) $liked_songs[] = $row;

// ── Top 3 genres by play count ────────────────────────────────
$top_genres = [];
$res = $conn->query(
    "SELECT s.genre, SUM(ph.play_count) AS total_plays
     FROM play_history ph
     JOIN songs s ON s.id = ph.song_id
     WHERE ph.user_id = $user_id
     GROUP BY s.genre
     ORDER BY total_plays DESC
     LIMIT 3"
);
while ($row = $res->fetch_assoc()) $top_genres[] = $row;

// For each top genre, pull top 10 songs by this user's play count
$genre_playlists = [];
foreach ($top_genres as $tg) {
    $g   = $conn->real_escape_string($tg['genre']);
    $res = $conn->query(
        "SELECT s.id, s.title, s.artist, s.cover_path, s.mood_tag,
                s.duration, s.is_explicit, ph.play_count
         FROM play_history ph
         JOIN songs s ON s.id = ph.song_id
         WHERE ph.user_id = $user_id AND s.genre = '$g'
         ORDER BY ph.play_count DESC
         LIMIT 10"
    );
    $songs = [];
    while ($row = $res->fetch_assoc()) $songs[] = $row;
    if ($songs) {
        $genre_playlists[] = [
            'genre'       => $tg['genre'],
            'total_plays' => $tg['total_plays'],
            'songs'       => $songs,
            'cover'       => $songs[0]['cover_path'] ?? null,
        ];
    }
}

// ── Top 20 played songs ───────────────────────────────────────
$top_played = [];
$res = $conn->query(
    "SELECT s.id, s.title, s.artist, s.cover_path, s.genre,
            s.mood_tag, s.duration, s.is_explicit, ph.play_count
     FROM play_history ph
     JOIN songs s ON s.id = ph.song_id
     WHERE ph.user_id = $user_id
     ORDER BY ph.play_count DESC
     LIMIT 20"
);
while ($row = $res->fetch_assoc()) $top_played[] = $row;

// ── Build JS-safe song ID arrays for locked playlist contexts ─
// These are passed to player.php via URL so get_queue.php can
// reconstruct the exact playlist without hitting DB differently.
// We pass them as a comma-separated list in the `value` param.
$liked_ids   = implode(',', array_column($liked_songs, 'id'));
$top_ids     = implode(',', array_column($top_played,  'id'));

$mood_emojis = ['happy'=>'😄','chill'=>'😌','focus'=>'🎯','sad'=>'🌧️','hype'=>'🔥'];

$page_title = 'Library';
require_once 'includes/header.php';
?>

<div class="library-page">

  <!-- ── Page header ─────────────────────────────────────── -->
  <div class="library-header">
    <h1 class="library-title">Your Library</h1>
    <p class="library-sub">Everything you've liked, played, and loved.</p>
  </div>

  <!-- ── Section tabs ────────────────────────────────────── -->
  <div class="lib-tabs">
    <button class="lib-tab active" data-tab="liked">
      <i class="ri-heart-3-fill"></i> Liked Songs
      <span class="tab-count"><?= count($liked_songs) ?></span>
    </button>
    <button class="lib-tab" data-tab="genres">
      <i class="ri-music-2-line"></i> Genre Playlists
      <span class="tab-count"><?= count($genre_playlists) ?></span>
    </button>
    <button class="lib-tab" data-tab="top">
      <i class="ri-bar-chart-fill"></i> Most Played
      <span class="tab-count"><?= count($top_played) ?></span>
    </button>
  </div>

  <!-- ══════════════════════════════════════════════════════
       TAB: Liked Songs
  ═══════════════════════════════════════════════════════════ -->
  <div class="lib-panel active" id="tab-liked">
    <?php if (empty($liked_songs)): ?>
      <div class="lib-empty">
        <i class="ri-heart-3-line lib-empty-icon"></i>
        <p>No liked songs yet. Hit the heart on any song in the player.</p>
        <a href="/groovekut/dashboard.php" class="btn-primary">Start Listening</a>
      </div>
    <?php else: ?>
      <div class="lib-song-list">
        <?php foreach ($liked_songs as $i => $s): ?>
          <!-- context=liked locks queue to this liked songs playlist -->
          <a href="/groovekut/player.php?id=<?= $s['id'] ?>&context=liked&value=<?= urlencode($liked_ids) ?>" class="lib-row">
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
            <span class="tag-mood mood-<?= $s['mood_tag'] ?>"><?= ucfirst($s['mood_tag']) ?></span>
            <span class="lib-row-duration">
              <?php if ($s['duration']): ?>
                <?= floor($s['duration']/60).':'.str_pad($s['duration']%60,2,'0',STR_PAD_LEFT) ?>
              <?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ══════════════════════════════════════════════════════
       TAB: Genre Playlists
  ═══════════════════════════════════════════════════════════ -->
  <div class="lib-panel" id="tab-genres">
    <?php if (empty($genre_playlists)): ?>
      <div class="lib-empty">
        <i class="ri-music-2-line lib-empty-icon"></i>
        <p>Play more songs to generate your genre playlists.</p>
        <a href="/groovekut/dashboard.php" class="btn-primary">Start Listening</a>
      </div>
    <?php else: ?>
      <?php foreach ($genre_playlists as $pl): ?>
        <div class="genre-playlist-block">
          <div class="gpl-header">
            <div class="gpl-cover">
              <?php if ($pl['cover']): ?>
                <img src="/groovekut/<?= htmlspecialchars($pl['cover']) ?>" alt="cover"/>
              <?php else: ?>
                <div class="gpl-cover-ph">🎵</div>
              <?php endif; ?>
            </div>
            <div class="gpl-meta">
              <h3 class="gpl-title"><?= htmlspecialchars($pl['genre']) ?></h3>
              <p class="gpl-sub"><?= $pl['total_plays'] ?> plays · <?= count($pl['songs']) ?> songs</p>
            </div>
          </div>

          <div class="lib-song-list">
            <?php
            // Build ordered ID list for this genre playlist — same pattern as liked/top
            $genre_ids = implode(',', array_column($pl['songs'], 'id'));
            ?>
            <?php foreach ($pl['songs'] as $i => $s): ?>
              <!-- context=genre-playlist locks queue to this specific genre playlist's songs -->
              <!-- context=genre (without -playlist) is reserved for smart genre queue from dashboard/search -->
              <a href="/groovekut/player.php?id=<?= $s['id'] ?>&context=genre-playlist&value=<?= urlencode($genre_ids) ?>&label=<?= urlencode($pl['genre']) ?>"
                 class="lib-row">
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
                <span class="lib-row-plays"><?= $s['play_count'] ?>× played</span>
                <span class="lib-row-duration">
                  <?php if ($s['duration']): ?>
                    <?= floor($s['duration']/60).':'.str_pad($s['duration']%60,2,'0',STR_PAD_LEFT) ?>
                  <?php endif; ?>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ══════════════════════════════════════════════════════
       TAB: Most Played
  ═══════════════════════════════════════════════════════════ -->
  <div class="lib-panel" id="tab-top">
    <?php if (empty($top_played)): ?>
      <div class="lib-empty">
        <i class="ri-bar-chart-line lib-empty-icon"></i>
        <p>No play history yet. Start listening to build your top songs.</p>
        <a href="/groovekut/dashboard.php" class="btn-primary">Start Listening</a>
      </div>
    <?php else: ?>
      <div class="lib-song-list">
        <?php foreach ($top_played as $i => $s): ?>
          <!-- context=top locks queue to this most played playlist -->
          <a href="/groovekut/player.php?id=<?= $s['id'] ?>&context=top&value=<?= urlencode($top_ids) ?>" class="lib-row">
            <span class="lib-row-num <?= $i < 3 ? 'top-'.$i : '' ?>"><?= $i + 1 ?></span>
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
            <span class="lib-row-duration">
              <?php if ($s['duration']): ?>
                <?= floor($s['duration']/60).':'.str_pad($s['duration']%60,2,'0',STR_PAD_LEFT) ?>
              <?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
// Tab switching
const tabs   = document.querySelectorAll('.lib-tab');
const panels = document.querySelectorAll('.lib-panel');

tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    tabs.forEach(t   => t.classList.remove('active'));
    panels.forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
  });
});
</script>

<?php require_once 'includes/footer.php'; ?>