<?php
// ============================================================
// GrooveKut — Search
// File: search.php
// ============================================================

require_once 'includes/session_helper.php';
require_login();

$query     = trim($_GET['q']     ?? '');
$genre_f   = trim($_GET['genre'] ?? '');
$mood_f    = trim($_GET['mood']  ?? '');

$valid_moods  = ['happy', 'chill', 'focus', 'sad', 'hype'];
$valid_genres = ['Pop', 'Hip-Hop', 'Electronic', 'Indie', 'R&B', 'Rock', 'Classical', 'Punjabi', 'Bollywood'];

// Sanitise filters
if (!in_array($mood_f,  $valid_moods))  $mood_f  = '';
if (!in_array($genre_f, $valid_genres)) $genre_f = '';

// Build SQL
$where    = [];
$results  = [];
$searched = false;

if ($query !== '' || $genre_f !== '' || $mood_f !== '') {
    $searched = true;

    if ($query !== '') {
        $q = $conn->real_escape_string($query);
        $where[] = "(s.title LIKE '%$q%' OR s.artist LIKE '%$q%')";
    }
    if ($genre_f !== '') {
        $g = $conn->real_escape_string($genre_f);
        $where[] = "s.genre = '$g'";
    }
    if ($mood_f !== '') {
        $m = $conn->real_escape_string($mood_f);
        $where[] = "s.mood_tag = '$m'";
    }

    $sql = "SELECT s.id, s.title, s.artist, s.genre, s.mood_tag,
                   s.cover_path, s.duration, s.is_explicit,
                   COALESCE(ph.play_count, 0) AS play_count,
                   IF(ls.id IS NOT NULL, 1, 0) AS is_liked
            FROM songs s
            LEFT JOIN play_history ph ON ph.song_id = s.id AND ph.user_id = " . current_user_id() . "
            LEFT JOIN liked_songs ls  ON ls.song_id = s.id AND ls.user_id = " . current_user_id() . "
            WHERE " . implode(' AND ', $where) . "
            ORDER BY play_count DESC, s.title ASC
            LIMIT 60";

    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $results[] = $row;
    }
}

// Get all genres for filter pills
$genre_rows = $conn->query("SELECT DISTINCT genre FROM songs ORDER BY genre");
$all_genres = [];
while ($g = $genre_rows->fetch_assoc()) $all_genres[] = $g['genre'];

$mood_emojis = ['happy'=>'😄','chill'=>'😌','focus'=>'🎯','sad'=>'🌧️','hype'=>'🔥'];

$page_title = 'Search';
require_once 'includes/header.php';
?>

<div class="search-page">

  <!-- ── Search bar ──────────────────────────────────────── -->
  <div class="search-hero">
    <h1 class="search-title">Search</h1>
    <div class="search-bar-wrap">
      <i class="ri-search-line search-icon"></i>
      <input
        type="text"
        id="search-input"
        class="search-input"
        placeholder="Song title or artist..."
        value="<?= htmlspecialchars($query) ?>"
        autofocus
      />
      <?php if ($query): ?>
        <a href="/groovekut/search.php" class="search-clear" title="Clear">
          <i class="ri-close-line"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Filters ─────────────────────────────────────────── -->
  <div class="filter-row">

    <!-- Genre pills -->
    <div class="filter-group">
      <span class="filter-label">Genre</span>
      <div class="filter-pills">
        <a href="<?= '?q='.urlencode($query).'&mood='.urlencode($mood_f) ?>"
           class="filter-pill <?= $genre_f === '' ? 'active' : '' ?>">All</a>
        <?php foreach ($all_genres as $g): ?>
          <a href="<?= '?q='.urlencode($query).'&genre='.urlencode($g).'&mood='.urlencode($mood_f) ?>"
             class="filter-pill <?= $genre_f === $g ? 'active' : '' ?>">
            <?= htmlspecialchars($g) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Mood pills -->
    <div class="filter-group">
      <span class="filter-label">Mood</span>
      <div class="filter-pills">
        <a href="<?= '?q='.urlencode($query).'&genre='.urlencode($genre_f) ?>"
           class="filter-pill <?= $mood_f === '' ? 'active' : '' ?>">All</a>
        <?php foreach ($valid_moods as $m): ?>
          <a href="<?= '?q='.urlencode($query).'&genre='.urlencode($genre_f).'&mood='.urlencode($m) ?>"
             class="filter-pill mood-pill mood-<?= $m ?> <?= $mood_f === $m ? 'active' : '' ?>">
            <?= $mood_emojis[$m] ?> <?= ucfirst($m) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- ── Results ─────────────────────────────────────────── -->
  <?php if ($searched): ?>

    <div class="search-meta">
      <?php if (count($results)): ?>
        <span><?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?>
        <?= $query ? ' for "<strong>'.htmlspecialchars($query).'</strong>"' : '' ?>
        </span>
      <?php else: ?>
        <span>No songs found. Try a different search or filter.</span>
      <?php endif; ?>
    </div>

    <?php if (count($results)): ?>
      <div class="search-results">
        <?php foreach ($results as $s): ?>
          <a href="/groovekut/player.php?id=<?= $s['id'] ?>" class="result-row">

            <div class="result-cover">
              <?php if ($s['cover_path']): ?>
                <img src="/groovekut/<?= htmlspecialchars($s['cover_path']) ?>" alt="cover"/>
              <?php else: ?>
                <div class="cover-placeholder sm">🎵</div>
              <?php endif; ?>
              <div class="result-play-overlay"><i class="ri-play-fill"></i></div>
            </div>

            <div class="result-info">
              <p class="result-title">
                <?= htmlspecialchars($s['title']) ?>
                <?php if ($s['is_explicit']): ?>
                  <span class="tag-explicit sm">E</span>
                <?php endif; ?>
              </p>
              <p class="result-artist"><?= htmlspecialchars($s['artist']) ?></p>
            </div>

            <div class="result-meta">
              <span class="result-genre"><?= htmlspecialchars($s['genre']) ?></span>
              <span class="tag-mood mood-<?= $s['mood_tag'] ?>"><?= ucfirst($s['mood_tag']) ?></span>
            </div>

            <div class="result-right">
              <?php if ($s['is_liked']): ?>
                <i class="ri-heart-3-fill result-heart liked"></i>
              <?php endif; ?>
              <?php if ($s['duration']): ?>
                <span class="result-duration">
                  <?= floor($s['duration']/60).':'.str_pad($s['duration']%60,2,'0',STR_PAD_LEFT) ?>
                </span>
              <?php endif; ?>
            </div>

          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>

    <!-- Browse state — nothing searched yet -->
    <div class="search-browse">
      <h2 class="section-title">Browse by Genre</h2>
      <div class="genre-browse-row">
        <?php foreach ($all_genres as $g): ?>
          <a href="/groovekut/search.php?genre=<?= urlencode($g) ?>"
             class="genre-browse-card">
            <?= htmlspecialchars($g) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <h2 class="section-title" style="margin-top:40px;">Browse by Mood</h2>
      <div class="mood-row">
        <?php foreach ($mood_emojis as $m => $emoji): ?>
          <a href="/groovekut/search.php?mood=<?= $m ?>" class="mood-card mood-<?= $m ?>">
            <span class="mood-emoji"><?= $emoji ?></span>
            <span class="mood-name"><?= ucfirst($m) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

  <?php endif; ?>

</div>

<script>
// Live search on Enter or 500ms debounce
const input = document.getElementById('search-input');
let debounce;

input.addEventListener('input', () => {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    const q    = input.value.trim();
    const url  = new URL(window.location.href);
    url.searchParams.set('q', q);
    if (q.length > 1 || q === '') window.location.href = url.toString();
  }, 500);
});

input.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    clearTimeout(debounce);
    const url = new URL(window.location.href);
    url.searchParams.set('q', input.value.trim());
    window.location.href = url.toString();
  }
});
</script>

<?php require_once 'includes/footer.php'; ?>
