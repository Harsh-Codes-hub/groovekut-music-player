<?php
// ============================================================
// GrooveKut — Recommendations Page
// File: recommendations.php
// ============================================================

require_once 'includes/session_helper.php';
require_login();

$mood     = current_mood();
$username = $_SESSION['username'];

$mood_labels = [
    'happy' => ['label' => 'Happy',  'emoji' => '😄'],
    'chill' => ['label' => 'Chill',  'emoji' => '😌'],
    'focus' => ['label' => 'Focus',  'emoji' => '🎯'],
    'sad'   => ['label' => 'Sad',    'emoji' => '🌧️'],
    'hype'  => ['label' => 'Hype',   'emoji' => '🔥'],
];

$page_title = 'For You';
require_once 'includes/header.php';
?>

<div class="rec-page">

  <!-- ── Page header ─────────────────────────────────────── -->
  <section class="rec-header">
    <div class="rec-header-text">
      <h1 class="rec-title">Made for You</h1>
      <p class="rec-subtitle">
        Scored from your plays, likes, and current mood —
        <span class="mood-inline mood-<?= $mood ?>">
          <?= $mood_labels[$mood]['emoji'] ?> <?= $mood_labels[$mood]['label'] ?>
        </span>
      </p>
    </div>

    <!-- Mood switcher inline -->
    <div class="rec-mood-switcher">
      <span class="switcher-label">Switch mood</span>
      <div class="mood-chips" id="rec-mood-chips">
        <?php foreach ($mood_labels as $m => $info): ?>
          <button
            class="mood-chip <?= $m === $mood ? 'active' : '' ?>"
            data-mood="<?= $m ?>"
            title="<?= $info['label'] ?>"
          >
            <span class="chip-emoji"><?= $info['emoji'] ?></span>
            <span class="chip-label"><?= $info['label'] ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ── Score legend ────────────────────────────────────── -->
  <div class="score-legend">
    <span class="legend-item"><span class="legend-dot liked"></span> Liked ×3</span>
    <span class="legend-item"><span class="legend-dot mood"></span> Mood ×2</span>
    <span class="legend-item"><span class="legend-dot genre"></span> Genre ×1.5</span>
    <span class="legend-item"><span class="legend-dot plays"></span> Plays ×1</span>
  </div>

  <!-- ── Results grid ────────────────────────────────────── -->
  <section class="rec-grid-section">
    <div class="rec-grid" id="rec-grid">
      <!-- Skeleton loaders -->
      <?php for ($i = 0; $i < 20; $i++): ?>
        <div class="rec-card skeleton"></div>
      <?php endfor; ?>
    </div>

    <div class="rec-empty" id="rec-empty" style="display:none;">
      <span class="empty-icon">🎵</span>
      <p>Play a few songs first — GrooveKut needs data to build your recommendations.</p>
      <a href="/groovekut/dashboard.php" class="btn-primary">Go Explore</a>
    </div>
  </section>

</div>

<script>
// ════════════════════════════════════════════════════════════
// Recommendations Page JS
// ════════════════════════════════════════════════════════════

const grid      = document.getElementById('rec-grid');
const emptyMsg  = document.getElementById('rec-empty');
const moodChips = document.querySelectorAll('#rec-mood-chips .mood-chip');
const moodInline = document.querySelector('.mood-inline');

// ── Fetch + render recommendations ───────────────────────────
async function loadRecs(mood) {
  // Reset to skeletons
  grid.innerHTML = '';
  for (let i = 0; i < 20; i++) {
    const sk = document.createElement('div');
    sk.className = 'rec-card skeleton';
    grid.appendChild(sk);
  }
  emptyMsg.style.display = 'none';

  try {
    const res  = await fetch('/groovekut/api/rec_engine.php?limit=20&include_liked=1');
    const data = await res.json();

    if (!data.success || !data.songs.length) {
      grid.innerHTML = '';
      emptyMsg.style.display = 'flex';
      return;
    }

    grid.innerHTML = '';
    data.songs.forEach((song, index) => {
      grid.appendChild(buildCard(song, index));
    });

  } catch (err) {
    console.error('Rec engine error:', err);
    grid.innerHTML = '';
    emptyMsg.style.display = 'flex';
  }
}

// ── Build a single rec card ───────────────────────────────────
function buildCard(song, index) {
  const card = document.createElement('a');
  card.href = `/groovekut/player.php?id=${song.id}`;
  card.className = 'rec-card';
  card.style.animationDelay = `${index * 40}ms`;

  const coverHTML = song.cover_path
    ? `<img src="/groovekut/${song.cover_path}" alt="cover" loading="lazy" />`
    : `<div class="cover-placeholder">🎵</div>`;

  // Score badge signals
  const signals = [];
  if (song.is_liked)    signals.push(`<span class="sig liked" title="Liked">♥</span>`);
  if (song.mood_match)  signals.push(`<span class="sig mood" title="Mood match">◉</span>`);
  if (song.genre_match) signals.push(`<span class="sig genre" title="Genre match">▣</span>`);

  const rankNum = index + 1;

  card.innerHTML = `
    <div class="rec-cover">
      ${coverHTML}
      <span class="rec-rank">#${rankNum}</span>
    </div>
    <div class="rec-info">
      <p class="rec-song-title">${escHtml(song.title)}</p>
      <p class="rec-song-artist">${escHtml(song.artist)}</p>
      <div class="rec-meta">
        <span class="rec-genre">${escHtml(song.genre)}</span>
        <div class="rec-signals">${signals.join('')}</div>
      </div>
    </div>
    <div class="rec-score-bar">
      <div class="score-fill" style="height: ${Math.min(100, song.rec_score * 8)}%"></div>
    </div>
  `;

  return card;
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

// ── Mood chip switching ───────────────────────────────────────
moodChips.forEach(chip => {
  chip.addEventListener('click', async () => {
    const mood = chip.dataset.mood;

    // Optimistic UI update
    moodChips.forEach(c => c.classList.remove('active'));
    chip.classList.add('active');

    // Update inline mood label
    if (moodInline) {
      moodInline.className = `mood-inline mood-${mood}`;
      const emojis = { happy:'😄', chill:'😌', focus:'🎯', sad:'🌧️', hype:'🔥' };
      const labels = { happy:'Happy', chill:'Chill', focus:'Focus', sad:'Sad', hype:'Hype' };
      moodInline.textContent = `${emojis[mood]} ${labels[mood]}`;
    }

    // POST to mood API
    const fd = new FormData();
    fd.append('mood', mood);
    await fetch('/groovekut/api/mood.php', { method: 'POST', body: fd });

    // Re-fetch recs for new mood
    loadRecs(mood);
  });
});

// ── Initial load ──────────────────────────────────────────────
loadRecs('<?= $mood ?>');
</script>

<?php require_once 'includes/footer.php'; ?>
