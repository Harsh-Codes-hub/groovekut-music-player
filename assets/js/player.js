// ============================================================
// GrooveKut — Player JS
// File: assets/js/player.js
// ============================================================

// ── State ────────────────────────────────────────────────────
const state = {
  queue:        [],
  queueIndex:   0,
  shuffle:      false,
  shuffleOrder: [],
  loop:         'off',      // 'off' | 'all' | 'one'
  autoplay:     true,
  muted:        false,
  volume:       80,
  playing:      false,
  loggedPlay:   false,      // has this play been logged to DB?
};

// ── DOM refs ─────────────────────────────────────────────────
const audio         = document.getElementById('groovekut-audio');
const playBtn       = document.getElementById('play-btn');
const playIcon      = document.getElementById('play-icon');
const prevBtn       = document.getElementById('prev-btn');
const nextBtn       = document.getElementById('next-btn');
const shuffleBtn    = document.getElementById('shuffle-btn');
const loopBtn       = document.getElementById('loop-btn');
const autoplayBtn   = document.getElementById('autoplay-btn');
const autoplayLabel = document.getElementById('autoplay-label');
const muteBtn       = document.getElementById('mute-btn');
const volumeIcon    = document.getElementById('volume-icon');
const volumeSlider  = document.getElementById('volume-slider');
const progressTrack = document.getElementById('progress-track');
const progressFill  = document.getElementById('progress-fill');
const progressThumb = document.getElementById('progress-thumb');
const timeCurrent   = document.getElementById('time-current');
const timeTotal     = document.getElementById('time-total');
const likeBtn       = document.getElementById('like-btn');
const queueList     = document.getElementById('queue-list');
const playerBg      = document.getElementById('player-bg');
const visualCanvas  = document.getElementById('visualizer-canvas');
const coverImg      = document.getElementById('player-cover-img');

// ── Visualizer setup ─────────────────────────────────────────
let audioCtx, analyser, source, visualRaf;

function initVisualizer() {
  if (audioCtx) return;
  try {
    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    analyser  = audioCtx.createAnalyser();
    analyser.fftSize = 128;
    source    = audioCtx.createMediaElementSource(audio);
    source.connect(analyser);
    analyser.connect(audioCtx.destination);
    drawVisualizer();
  } catch(e) {
    console.warn('Visualizer unavailable:', e);
  }
}

function drawVisualizer() {
  if (!visualCanvas || !analyser) return;
  const ctx    = visualCanvas.getContext('2d');
  const W      = visualCanvas.width  = window.innerWidth;
  const H      = visualCanvas.height = window.innerHeight;
  const data   = new Uint8Array(analyser.frequencyBinCount);

  function frame() {
    visualRaf = requestAnimationFrame(frame);
    analyser.getByteFrequencyData(data);
    ctx.clearRect(0, 0, W, H);

    const barW   = W / data.length * 2.5;
    const gap    = 2;
    let x        = 0;

    for (let i = 0; i < data.length; i++) {
      const barH = (data[i] / 255) * (H * 0.35);
      // Draw from bottom center outward
      ctx.fillStyle = `rgba(${currentRGB}, ${0.15 + (data[i]/255)*0.2})`;
      ctx.beginPath();
      ctx.roundRect(x, H - barH, barW - gap, barH, 3);
      ctx.fill();
      x += barW;
    }
  }
  frame();
}

// ── Color extraction ─────────────────────────────────────────
let currentRGB = '55, 138, 221'; // default accent

function extractColor(imgEl) {
  if (!imgEl) return;
  const canvas = document.getElementById('color-extractor');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  try {
    ctx.drawImage(imgEl, 0, 0, 50, 50);
    const pixels = ctx.getImageData(0, 0, 50, 50).data;
    let r = 0, g = 0, b = 0, count = 0;
    for (let i = 0; i < pixels.length; i += 16) {
      r += pixels[i];
      g += pixels[i + 1];
      b += pixels[i + 2];
      count++;
    }
    r = Math.round(r / count);
    g = Math.round(g / count);
    b = Math.round(b / count);
    // Boost saturation slightly so it reads as color not grey
    currentRGB = `${r}, ${g}, ${b}`;
    applyBgColor(r, g, b);
  } catch(e) {
    applyMoodFallback();
  }
}

function applyBgColor(r, g, b) {
  if (playerBg) {
    playerBg.style.background =
      `radial-gradient(ellipse at 30% 40%, rgba(${r},${g},${b},0.35) 0%, transparent 65%),
       radial-gradient(ellipse at 70% 60%, rgba(${r},${g},${b},0.2) 0%, transparent 55%)`;
  }
}

function applyMoodFallback() {
  const moodMap = {
    happy: '180,120,40', chill: '30,80,160',
    hype:  '180,40,30',  sad:   '80,40,140', focus: '20,120,120'
  };
  const rgb = moodMap[INITIAL_SONG.mood] || '55,138,221';
  currentRGB = rgb;
  const [r,g,b] = rgb.split(',').map(Number);
  applyBgColor(r, g, b);
}

// Run color extraction when cover loads
if (coverImg) {
  if (coverImg.complete) extractColor(coverImg);
  else coverImg.addEventListener('load', () => extractColor(coverImg));
} else {
  applyMoodFallback();
}

// ── Queue ─────────────────────────────────────────────────────
async function buildQueue() {
  queueList.innerHTML = '<p class="queue-loading">Building queue...</p>';
  try {
    const url = `/groovekut/api/get_queue.php?song_id=${INITIAL_SONG.id}&context=${QUEUE_CONTEXT}&value=${encodeURIComponent(QUEUE_VALUE)}`;
    const res  = await fetch(url);
    const data = await res.json();

    if (data.success && data.queue.length) {
      state.queue      = data.queue;
      state.queueIndex = 0;
      state.shuffleOrder = generateShuffleOrder(data.queue.length);
      renderQueue();
      saveQueueToSession();
    }
  } catch(e) {
    queueList.innerHTML = '<p class="queue-loading">Could not load queue.</p>';
  }
}

function generateShuffleOrder(len) {
  const arr = Array.from({length: len}, (_, i) => i);
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}

function renderQueue() {
  queueList.innerHTML = '';
  const displayOrder = state.shuffle ? state.shuffleOrder : state.queue.map((_, i) => i);

  displayOrder.forEach((songIdx, displayIdx) => {
    const s    = state.queue[songIdx];
    const item = document.createElement('div');
    item.className = 'queue-item' + (songIdx === state.queueIndex ? ' active' : '');
    item.dataset.idx = songIdx;

    item.innerHTML = `
      <div class="qi-cover">
        ${s.cover_path
          ? `<img src="/groovekut/${s.cover_path}" alt="cover"/>`
          : `<div class="qi-cover-ph"><i class="ri-music-2-line"></i></div>`}
      </div>
      <div class="qi-info">
        <p class="qi-title">${escHtml(s.title)}</p>
        <p class="qi-artist">${escHtml(s.artist)}</p>
      </div>
      <span class="qi-genre">${escHtml(s.genre)}</span>`;

    item.addEventListener('click', () => {
      state.queueIndex = songIdx;
      loadSong(state.queue[songIdx]);
    });
    queueList.appendChild(item);
  });
}

function saveQueueToSession() {
  sessionStorage.setItem('gk_queue', JSON.stringify(state.queue));
  sessionStorage.setItem('gk_qi',    state.queueIndex);
}

// ── Load song ─────────────────────────────────────────────────
function loadSong(song) {
  // Update audio src
  audio.src = `/groovekut/${song.file_path}`;
  audio.load();

  // Update UI text
  document.getElementById('player-title').textContent  = song.title;
  document.getElementById('player-artist').textContent = song.artist;
  document.getElementById('player-genre').textContent  = song.genre;

  const moodEl = document.getElementById('player-mood');
  moodEl.textContent = song.mood_tag ? song.mood_tag.charAt(0).toUpperCase() + song.mood_tag.slice(1) : '';
  moodEl.className   = `tag-mood mood-${song.mood_tag}`;

  // Update cover
  const coverEl = document.querySelector('.player-cover');
  if (coverEl && song.cover_path) {
    coverEl.src = `/groovekut/${song.cover_path}`;
    coverEl.onload = () => extractColor(coverEl);
  } else {
    applyMoodFallback();
  }

  // Update duration
  const dur = song.duration || 0;
  timeTotal.textContent = formatTime(dur);

  // Reset progress
  progressFill.style.width = '0%';
  timeCurrent.textContent  = '0:00';

  // Reset like state (we'd need to check DB — simplified: reset to unliked)
  if (likeBtn) {
    likeBtn.dataset.songId = song.id;
    likeBtn.classList.remove('liked');
    likeBtn.querySelector('i').className = 'ri-heart-3-line';
  }

  // Update page title
  document.title = `${song.title} — ${song.artist} | GrooveKut`;

  // Update navbar now-playing
  updateNowPlaying(song);

  // Reset log flag
  state.loggedPlay = false;

  // Render queue highlight
  renderQueue();
  saveQueueToSession();

  // Auto play
  playAudio();
}

// ── Play / Pause ──────────────────────────────────────────────
async function playAudio() {
  // Gate check for guests
  if (!IS_LOGGED_IN) {
    const res  = await fetch('/groovekut/api/session_check.php');
    const data = await res.json();
    if (!data.success && data.gate) {
      showGateModal();
      return;
    }
  }

  initVisualizer();
  if (audioCtx && audioCtx.state === 'suspended') {
    audioCtx.resume();
  }

  audio.play().then(() => {
    state.playing = true;
    playIcon.className = 'ri-pause-fill';
  }).catch(e => console.warn('Play error:', e));
}

function pauseAudio() {
  audio.pause();
  state.playing = false;
  playIcon.className = 'ri-play-fill';
}

// ── Navigation ────────────────────────────────────────────────
function getNextIndex() {
  if (state.shuffle) {
    const pos = state.shuffleOrder.indexOf(state.queueIndex);
    const next = (pos + 1) % state.shuffleOrder.length;
    return state.shuffleOrder[next];
  }
  return (state.queueIndex + 1) % state.queue.length;
}

function getPrevIndex() {
  if (state.shuffle) {
    const pos = state.shuffleOrder.indexOf(state.queueIndex);
    const prev = (pos - 1 + state.shuffleOrder.length) % state.shuffleOrder.length;
    return state.shuffleOrder[prev];
  }
  return (state.queueIndex - 1 + state.queue.length) % state.queue.length;
}

function playNext() {
  if (!state.queue.length) return;
  state.queueIndex = getNextIndex();
  loadSong(state.queue[state.queueIndex]);
}

function playPrev() {
  // If more than 3s in, restart. Else go prev.
  if (audio.currentTime > 3) {
    audio.currentTime = 0;
    return;
  }
  if (!state.queue.length) return;
  state.queueIndex = getPrevIndex();
  loadSong(state.queue[state.queueIndex]);
}

// ── Song ended ────────────────────────────────────────────────
audio.addEventListener('ended', () => {
  if (state.loop === 'one') {
    audio.currentTime = 0;
    playAudio();
  } else if (state.loop === 'all' || state.autoplay) {
    playNext();
  } else {
    state.playing = false;
    playIcon.className = 'ri-play-fill';
  }
});

// ── Log play to DB ────────────────────────────────────────────
audio.addEventListener('timeupdate', () => {
  if (!IS_LOGGED_IN) return;
  // Log after 10 seconds of play
  if (!state.loggedPlay && audio.currentTime >= 10) {
    state.loggedPlay = true;
    const currentSong = state.queue[state.queueIndex] || INITIAL_SONG;
    const fd = new FormData();
    fd.append('song_id', currentSong.id);
    fetch('/groovekut/api/log_play.php', { method: 'POST', body: fd });
  }
});

// ── Progress bar ──────────────────────────────────────────────
audio.addEventListener('timeupdate', () => {
  if (!audio.duration) return;
  const pct = (audio.currentTime / audio.duration) * 100;
  progressFill.style.width = pct + '%';
  progressThumb.style.left = pct + '%';
  timeCurrent.textContent  = formatTime(audio.currentTime);
  if (audio.duration) timeTotal.textContent = formatTime(audio.duration);
});

let seeking = false;
progressTrack.addEventListener('mousedown', e => { seeking = true; seek(e); });
document.addEventListener('mousemove',  e => { if (seeking) seek(e); });
document.addEventListener('mouseup',    ()  => { seeking = false; });
progressTrack.addEventListener('touchstart', e => { seeking = true; seek(e.touches[0]); }, {passive:true});
document.addEventListener('touchmove',  e => { if (seeking) seek(e.touches[0]); }, {passive:true});
document.addEventListener('touchend',   ()  => { seeking = false; });

function seek(e) {
  const rect = progressTrack.getBoundingClientRect();
  const pct  = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
  if (audio.duration) audio.currentTime = pct * audio.duration;
}

// ── Volume ────────────────────────────────────────────────────
volumeSlider.addEventListener('input', () => {
  state.volume  = volumeSlider.value;
  audio.volume  = state.volume / 100;
  state.muted   = state.volume == 0;
  updateVolumeIcon();
});

muteBtn.addEventListener('click', () => {
  state.muted  = !state.muted;
  audio.muted  = state.muted;
  updateVolumeIcon();
});

function updateVolumeIcon() {
  if (state.muted || state.volume == 0) {
    volumeIcon.className = 'ri-volume-mute-line';
  } else if (state.volume < 50) {
    volumeIcon.className = 'ri-volume-down-line';
  } else {
    volumeIcon.className = 'ri-volume-up-line';
  }
}

audio.volume = state.volume / 100;

// ── Loop ──────────────────────────────────────────────────────
loopBtn.addEventListener('click', () => {
  const modes = ['off', 'all', 'one'];
  const idx   = modes.indexOf(state.loop);
  state.loop  = modes[(idx + 1) % modes.length];
  updateLoopBtn();
});

function updateLoopBtn() {
  const icons = {
    off: 'ri-repeat-line',
    all: 'ri-repeat-line',
    one: 'ri-repeat-one-line',
  };
  loopBtn.querySelector('i').className = icons[state.loop];
  loopBtn.classList.toggle('active', state.loop !== 'off');
  loopBtn.title = `Loop: ${state.loop}`;
}

// ── Shuffle ───────────────────────────────────────────────────
shuffleBtn.addEventListener('click', () => {
  state.shuffle = !state.shuffle;
  if (state.shuffle) {
    state.shuffleOrder = generateShuffleOrder(state.queue.length);
  }
  shuffleBtn.classList.toggle('active', state.shuffle);
  shuffleBtn.title = state.shuffle ? 'Shuffle On' : 'Shuffle Off';
  renderQueue();
});

// ── Autoplay ──────────────────────────────────────────────────
autoplayBtn.addEventListener('click', () => {
  state.autoplay = !state.autoplay;
  autoplayBtn.classList.toggle('active', state.autoplay);
  autoplayLabel.textContent = state.autoplay ? 'Autoplay' : 'Autoplay Off';
  autoplayBtn.title = state.autoplay ? 'Autoplay On' : 'Autoplay Off';
});

// ── Play / Pause button ───────────────────────────────────────
playBtn.addEventListener('click', () => {
  if (state.playing) pauseAudio();
  else playAudio();
});

prevBtn.addEventListener('click', playPrev);
nextBtn.addEventListener('click', playNext);

// ── Like button ───────────────────────────────────────────────
if (likeBtn) {
  likeBtn.addEventListener('click', async () => {
    if (!IS_LOGGED_IN) {
      showGateModal();
      return;
    }
    const songId = likeBtn.dataset.songId;
    const fd     = new FormData();
    fd.append('song_id', songId);
    const res  = await fetch('/groovekut/api/like.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      likeBtn.classList.toggle('liked', data.liked);
      likeBtn.querySelector('i').className = data.liked ? 'ri-heart-3-fill' : 'ri-heart-3-line';
    }
  });
}

// ── Guest gate modal ──────────────────────────────────────────
function showGateModal() {
  const existing = document.getElementById('gate-modal');
  if (existing) return;

  const modal = document.createElement('div');
  modal.id = 'gate-modal';
  modal.className = 'modal-overlay';
  modal.innerHTML = `
    <div class="modal-card gate-card">
      <i class="ri-music-2-line gate-icon"></i>
      <h2>Keep the music going</h2>
      <p>Create a free account to keep listening, like songs, and get personalised recommendations.</p>
      <div class="gate-actions">
        <a href="/groovekut/auth/register.php" class="btn-primary">Sign Up Free</a>
        <a href="/groovekut/auth/login.php" class="btn-ghost">Log In</a>
      </div>
    </div>`;
  document.body.appendChild(modal);
}

// ── Navbar now-playing indicator ──────────────────────────────
function updateNowPlaying(song) {
  let indicator = document.getElementById('now-playing-indicator');
  if (!indicator) {
    indicator = document.createElement('div');
    indicator.id = 'now-playing-indicator';
    indicator.className = 'now-playing-indicator';
    document.querySelector('.navbar').appendChild(indicator);
  }
  indicator.innerHTML = `
    <span class="np-dot"></span>
    <span class="np-title">${escHtml(song.title)}</span>`;
  indicator.onclick = () => window.location.href = `/groovekut/player.php?id=${song.id}`;
  sessionStorage.setItem('gk_now_playing_id', song.id);
}

// ── Keyboard shortcuts ────────────────────────────────────────
document.addEventListener('keydown', e => {
  // Don't fire if typing in an input
  if (e.target.tagName === 'INPUT') return;
  if (e.code === 'Space') {
    e.preventDefault();
    if (state.playing) pauseAudio(); else playAudio();
  }
  if (e.code === 'ArrowRight') { e.preventDefault(); audio.currentTime += 10; }
  if (e.code === 'ArrowLeft')  { e.preventDefault(); audio.currentTime -= 10; }
  if (e.code === 'ArrowUp')    { e.preventDefault(); volumeSlider.value = Math.min(100, +volumeSlider.value + 10); volumeSlider.dispatchEvent(new Event('input')); }
  if (e.code === 'ArrowDown')  { e.preventDefault(); volumeSlider.value = Math.max(0,   +volumeSlider.value - 10); volumeSlider.dispatchEvent(new Event('input')); }
  if (e.code === 'KeyN')       playNext();
  if (e.code === 'KeyP')       playPrev();
});

// ── Helpers ───────────────────────────────────────────────────
function formatTime(secs) {
  secs = Math.floor(secs || 0);
  const m = Math.floor(secs / 60);
  const s = secs % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// ── Resize visualizer canvas ──────────────────────────────────
window.addEventListener('resize', () => {
  if (visualCanvas) {
    visualCanvas.width  = window.innerWidth;
    visualCanvas.height = window.innerHeight;
  }
});

// ── Init ──────────────────────────────────────────────────────
buildQueue();
updateNowPlaying(INITIAL_SONG);

// Auto-play on page load (requires user gesture — triggered by click on song card)
// If coming from a click, audio context will init on first playAudio call
audio.addEventListener('canplay', () => {
  if (!state.playing) playAudio();
}, { once: true });
