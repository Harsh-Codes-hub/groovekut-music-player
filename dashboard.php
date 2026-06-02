<?php
// ============================================================
// GrooveKut — Dashboard
// File: dashboard.php
// ============================================================

require_once 'includes/session_helper.php';

$is_guest = !is_logged_in();
$user_id  = $is_guest ? 0 : current_user_id();
$username = $is_guest ? 'Guest' : ($_SESSION['username'] ?? 'there');
$mood     = $is_guest ? 'chill' : current_mood();

// ── Fetch play history count ──────────────────────────────────
$history_count = 0;
if (!$is_guest) {
  $res = $conn->query("SELECT COUNT(*) as c FROM play_history WHERE user_id = $user_id");
  if ($res) $history_count = $res->fetch_assoc()['c'];
}

// ── Greeting by time ─────────────────────────────────────────
$hour = (int)date('G');
if ($hour < 12)      $greeting = 'Good morning';
elseif ($hour < 17)  $greeting = 'Good afternoon';
else                 $greeting = 'Good evening';

// ── Mood playlist rows (5 moods always exist) ────────────────
$moods = [
  'happy' => '😄',
  'chill' => '😌',
  'focus' => '🎯',
  'sad'   => '🌧️',
  'hype'  => '🔥',
];

$mood_labels = [
  'happy' => 'Happy',
  'chill' => 'Chill',
  'focus' => 'Focus',
  'sad'   => 'Sad',
  'hype'  => 'Hype',
];

// ── Genre browse cards (from songs table, distinct genres) ───
$genre_rows = $conn->query("SELECT DISTINCT genre FROM songs ORDER BY genre");
$genres_all = [];
while ($g = $genre_rows->fetch_assoc()) {
  $genres_all[] = $g['genre'];
}

// ── Recently played (last 6) ─────────────────────────────────
$recent_songs = [];
if ($history_count > 0) {
  $res = $conn->query(
    "SELECT s.id, s.title, s.artist, s.cover_path, s.mood_tag
         FROM play_history ph
         JOIN songs s ON s.id = ph.song_id
         WHERE ph.user_id = $user_id
         ORDER BY ph.played_at DESC
         LIMIT 6"
  );
  while ($row = $res->fetch_assoc()) {
    $recent_songs[] = $row;
  }
}

// ── New user: pull 12 random songs to fill the library row ───
$starter_songs = [];
$preferred = $is_guest ? null : ($_SESSION['preferred_genres'] ?? null);

if (!$is_guest && !$preferred) {
  $res = $conn->query("SELECT preferred_genres FROM users WHERE id = $user_id LIMIT 1");
  $row = $res->fetch_assoc();
  $preferred = $row['preferred_genres'] ?? null;
}

$genres_decoded = $preferred ? json_decode($preferred, true) : [];

if (!empty($genres_decoded)) {
  $genre_list = implode("','", array_map([$conn, 'real_escape_string'], $genres_decoded));
  $res = $conn->query("SELECT id, title, artist, cover_path, mood_tag FROM songs WHERE genre IN ('$genre_list') ORDER BY RAND($user_id) LIMIT 14");
} else {
  $res = $conn->query("SELECT id, title, artist, cover_path, mood_tag FROM songs ORDER BY RAND($user_id) LIMIT 12");
}

while ($row = $res->fetch_assoc()) {
  $starter_songs[] = $row;
}

$page_title = 'Home';
require_once 'includes/header.php';
?>

<!-- ══════════════════════════════════════════════════════════
     ONBOARDING MODAL — shows only on first login
═══════════════════════════════════════════════════════════════ -->
<?php if (!$is_guest && needs_onboarding()): ?>
  <div class="modal-overlay" id="onboarding-overlay">
    <div class="modal-card onboarding-card">

      <div class="onboarding-header">
        <h2>What do you want to hear? 🎵</h2>
        <p>Pick up to 3 genres and we'll set up your playlists instantly.</p>
      </div>

      <div class="genre-grid" id="genre-grid">
        <?php
        $genre_icons = [
          'Pop'        => '🎤',
          'Hip-Hop'    => '🎧',
          'Electronic' => '⚡',
          'Indie'      => '🎸',
          'R&B'        => '🎷',
          'Rock'       => '🤘',
          'Classical'  => '🎻',
          'Punjabi'    => '🥁',
          'Bollywood'  => '🎬',
        ];
        foreach ($genre_icons as $g => $icon): ?>
          <div class="genre-chip" data-genre="<?= $g ?>">
            <span class="genre-icon"><?= $icon ?></span>
            <span class="genre-label"><?= $g ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="onboarding-actions">
        <button class="btn-primary" id="save-genres-btn" disabled>Start Listening →</button>
        <button class="btn-ghost" id="skip-onboarding-btn">Skip for now</button>
      </div>

    </div>
  </div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     DASHBOARD CONTENT
═══════════════════════════════════════════════════════════════ -->

<?php if ($history_count >= 5): ?>
  <!-- RETURNING USER — rich dashboard -->

  <section class="dash-hero">
    <div class="hero-text">
      <h1><?= $greeting ?>, <span class="accent"><?= htmlspecialchars($username) ?></span> 👋</h1>
      <p>Here's what's waiting for you.</p>
    </div>

    <!-- Mood badge — click to open picker -->
    <div class="mood-badge-wrap">
      <button class="mood-badge mood-<?= $mood ?>" id="mood-badge" aria-expanded="false" title="Change mood">
        <span class="mood-badge-emoji"><?= $moods[$mood] ?></span>
        <span class="mood-badge-label" id="mood-badge-label"><?= $mood_labels[$mood] ?> vibes</span>
        <i class="ri-arrow-down-s-line mood-badge-arrow"></i>
      </button>

      <!-- Inline mood picker — hidden until badge clicked -->
      <div class="mood-picker-dropdown" id="mood-picker" role="menu" aria-hidden="true">
        <p class="picker-hint">How are you feeling?</p>
        <div class="mood-picker-chips">
          <?php foreach ($moods as $m => $emoji): ?>
            <button
              class="mood-picker-chip <?= $m === $mood ? 'active' : '' ?>"
              data-mood="<?= $m ?>"
              role="menuitem">
              <span class="picker-chip-emoji"><?= $emoji ?></span>
              <span class="picker-chip-label"><?= $mood_labels[$m] ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Mood rows -->
  <section class="dash-section">
    <h2 class="section-title">Browse by Mood</h2>
    <div class="mood-row">
      <?php foreach ($moods as $m => $emoji): ?>
        <div class="mood-card mood-<?= $m ?> <?= $m === $mood ? 'active-mood' : '' ?>"
          data-mood="<?= $m ?>"
          role="button"
          tabindex="0"
          title="Set mood to <?= ucfirst($m) ?>">
          <span class="mood-emoji"><?= $emoji ?></span>
          <span class="mood-name"><?= ucfirst($m) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Recently played -->
  <?php if (!empty($recent_songs)): ?>
    <section class="dash-section">
      <h2 class="section-title">Recently Played</h2>
      <div class="song-row">
        <?php foreach ($recent_songs as $s): ?>
          <a href="/groovekut/player.php?id=<?= $s['id'] ?>" class="song-card">
            <div class="song-cover">
              <?php if ($s['cover_path']): ?>
                <img src="/groovekut/<?= htmlspecialchars($s['cover_path']) ?>" alt="cover" />
              <?php else: ?>
                <div class="cover-placeholder">🎵</div>
              <?php endif; ?>
            </div>
            <div class="song-info">
              <p class="song-title"><?= htmlspecialchars($s['title']) ?></p>
              <p class="song-artist"><?= htmlspecialchars($s['artist']) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Recommendations link -->
  <section class="dash-section">
    <a href="/groovekut/recommendations.php" class="rec-banner">
      <span>🎯 See your personalised recommendations</span>
      <span class="arrow">→</span>
    </a>
  </section>

<?php else: ?>
  <!-- NEW / LOW-HISTORY USER — starter dashboard -->

  <section class="dash-hero">
    <div class="hero-text">
      <h1>Start Listening 🎵</h1>
      <p>Discover music. The more you play, the smarter GrooveKut gets.</p>
    </div>
    <a href="/groovekut/search.php" class="btn-primary hero-search-btn">Search Songs</a>
  </section>

  <!-- Browse by genre -->
  <section class="dash-section">
    <h2 class="section-title">Browse by Genre</h2>
    <div class="genre-browse-row">
      <?php foreach ($genres_all as $g): ?>
        <a href="/groovekut/library.php?genre=<?= urlencode($g) ?>" class="genre-browse-card">
          <?= htmlspecialchars($g) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Starter songs -->
  <section class="dash-section">
    <h2 class="section-title">Songs to Get You Started</h2>
    <div class="song-row">
      <?php foreach ($starter_songs as $s): ?>
        <a href="/groovekut/player.php?id=<?= $s['id'] ?>" class="song-card">
          <div class="song-cover">
            <?php if ($s['cover_path']): ?>
              <img src="/groovekut/<?= htmlspecialchars($s['cover_path']) ?>" alt="cover" />
            <?php else: ?>
              <div class="cover-placeholder">🎵</div>
            <?php endif; ?>
          </div>
          <div class="song-info">
            <p class="song-title"><?= htmlspecialchars($s['title']) ?></p>
            <p class="song-artist"><?= htmlspecialchars($s['artist']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

<?php endif; ?>

<script>
  // ════════════════════════════════════════════════════════════
  // Onboarding modal logic
  // ════════════════════════════════════════════════════════════
  const overlay = document.getElementById('onboarding-overlay');
  const saveBtn = document.getElementById('save-genres-btn');
  const skipBtn = document.getElementById('skip-onboarding-btn');
  const chips = document.querySelectorAll('.genre-chip');
  let selected = [];

  if (chips.length) {
    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        const genre = chip.dataset.genre;
        if (chip.classList.contains('selected')) {
          chip.classList.remove('selected');
          selected = selected.filter(g => g !== genre);
        } else if (selected.length < 3) {
          chip.classList.add('selected');
          selected.push(genre);
        }
        saveBtn.disabled = selected.length === 0;
      });
    });
  }

  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      const formData = new FormData();
      selected.forEach(g => formData.append('genres[]', g));
      const res = await fetch('/groovekut/api/onboarding_save.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        overlay.classList.add('fade-out');
        setTimeout(() => overlay.remove(), 400);
      }
    });
  }

  if (skipBtn) {
    skipBtn.addEventListener('click', async () => {
      const formData = new FormData();
      await fetch('/groovekut/api/onboarding_save.php', {
        method: 'POST',
        body: formData
      });
      overlay.classList.add('fade-out');
      setTimeout(() => overlay.remove(), 400);
    });
  }

  // ════════════════════════════════════════════════════════════
  // Mood cards — big squares set mood AND update badge
  // ════════════════════════════════════════════════════════════
  const moodCards = document.querySelectorAll('.mood-card[data-mood]');

  moodCards.forEach(card => {
    card.addEventListener('click', async () => {
      const mood = card.dataset.mood;

      // Update all mood cards active state
      moodCards.forEach(c => c.classList.remove('active-mood'));
      card.classList.add('active-mood');

      // Mirror to dropdown chips
      pickerChips.forEach(c => {
        c.classList.toggle('active', c.dataset.mood === mood);
      });

      // Update badge
      if (moodBadge && badgeLabel) {
        moodBadge.className = `mood-badge mood-${mood}`;
        moodBadge.id = 'mood-badge';
        badgeLabel.textContent = `${moodNames[mood]} vibes`;
      }

      closePicker();

      // POST to API
      const fd = new FormData();
      fd.append('mood', mood);
      await fetch('/groovekut/api/mood.php', {
        method: 'POST',
        body: fd
      });
    });

    // Keyboard support
    card.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') card.click();
    });
  });


  const moodBadge = document.getElementById('mood-badge');
  const moodPicker = document.getElementById('mood-picker');
  const badgeLabel = document.getElementById('mood-badge-label');
  const pickerChips = document.querySelectorAll('.mood-picker-chip');

  const moodEmojis = {
    happy: '😄',
    chill: '😌',
    focus: '🎯',
    sad: '🌧️',
    hype: '🔥'
  };
  const moodNames = {
    happy: 'Happy',
    chill: 'Chill',
    focus: 'Focus',
    sad: 'Sad',
    hype: 'Hype'
  };

  let pickerOpen = false;

  function openPicker() {
    pickerOpen = true;
    moodPicker.classList.add('open');
    moodBadge.setAttribute('aria-expanded', 'true');
    moodPicker.setAttribute('aria-hidden', 'false');
  }

  function closePicker() {
    pickerOpen = false;
    moodPicker.classList.remove('open');
    moodBadge.setAttribute('aria-expanded', 'false');
    moodPicker.setAttribute('aria-hidden', 'true');
  }

  if (moodBadge) {
    moodBadge.addEventListener('click', (e) => {
      e.stopPropagation();
      pickerOpen ? closePicker() : openPicker();
    });
  }

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (pickerOpen && !moodPicker.contains(e.target)) {
      closePicker();
    }
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && pickerOpen) closePicker();
  });

  // Chip selection
  pickerChips.forEach(chip => {
    chip.addEventListener('click', async () => {
      const mood = chip.dataset.mood;

      // Optimistic UI
      pickerChips.forEach(c => c.classList.remove('active'));
      chip.classList.add('active');

      // Update badge
      moodBadge.className = `mood-badge mood-${mood}`;
      moodBadge.id = 'mood-badge'; // keep ID
      badgeLabel.textContent = `${moodNames[mood]} vibes`;

      closePicker();

      // POST to API
      const fd = new FormData();
      fd.append('mood', mood);
      await fetch('/groovekut/api/mood.php', {
        method: 'POST',
        body: fd
      });
    });
  });
</script>

<?php require_once 'includes/footer.php'; ?>