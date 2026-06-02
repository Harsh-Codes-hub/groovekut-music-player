<?php
// ============================================================
// GrooveKut — Admin Add Song
// File: admin/add_song.php
// Uploads mp3 + jpg, auto-renames to convention, inserts DB row
// ============================================================

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /groovekut/admin/index.php');
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';

$error   = '';
$success = '';

$valid_genres = ['Pop','Hip-Hop','Electronic','Indie','R&B','Rock','Classical','Punjabi','Bollywood'];
$valid_moods  = ['happy','chill','focus','sad','hype'];

// ── Helper: slugify for filename ──────────────────────────────
function slugify(string $str): string {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    $str = trim($str, '-');
    // Keep max 5 words
    $parts = explode('-', $str);
    return implode('-', array_slice($parts, 0, 5));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Get next auto ID ──────────────────────────────────────
    $id_res = $conn->query("SELECT MAX(id) AS max_id FROM songs");
    $next_id = ((int)$id_res->fetch_assoc()['max_id']) + 1;
    $id_pad  = str_pad($next_id, 3, '0', STR_PAD_LEFT);

    // ── Collect fields ────────────────────────────────────────
    $title       = trim($_POST['title']       ?? '');
    $artist      = trim($_POST['artist']      ?? '');
    $genre       = trim($_POST['genre']       ?? '');
    $mood_tag    = trim($_POST['mood_tag']    ?? '');
    $manual_tags = trim($_POST['manual_tags'] ?? '');
    $is_explicit = intval($_POST['is_explicit'] ?? 0);
    $duration    = intval($_POST['duration']  ?? 0) ?: null;

    // Validation
    if (!$title || !$artist || !$genre || !$mood_tag) {
        $error = 'Title, artist, genre and mood are required.';
    } elseif (!in_array($genre,    $valid_genres)) {
        $error = 'Invalid genre.';
    } elseif (!in_array($mood_tag, $valid_moods)) {
        $error = 'Invalid mood.';
    } elseif (empty($_FILES['song_file']['name'])) {
        $error = 'Please upload an MP3 file.';
    } else {
        // ── Build slug filename ───────────────────────────────
        // Get first artist only for slug
        $first_artist = explode(',', $artist)[0];
        $slug = $id_pad . '-' . slugify($first_artist) . '-' . slugify($title);

        $song_filename  = $slug . '.mp3';
        $cover_filename = $slug . '.jpg';

        $songs_dir  = __DIR__ . '/../uploads/songs/';
        $covers_dir = __DIR__ . '/../uploads/covers/';

        $song_dest  = $songs_dir  . $song_filename;
        $cover_dest = $covers_dir . $cover_filename;

        // ── Upload song file ──────────────────────────────────
        $song_tmp  = $_FILES['song_file']['tmp_name'];
        $song_type = mime_content_type($song_tmp);

        if (!in_array($song_type, ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg'])) {
            $error = 'Song must be an MP3 file.';
        } elseif (!move_uploaded_file($song_tmp, $song_dest)) {
            $error = 'Failed to upload MP3. Check folder permissions.';
        } else {
            // ── Upload cover (optional) ───────────────────────
            $cover_path_db = null;
            if (!empty($_FILES['cover_file']['name'])) {
                $cover_tmp  = $_FILES['cover_file']['tmp_name'];
                $cover_type = mime_content_type($cover_tmp);

                if (in_array($cover_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])) {
                    // Convert webp/png to jpg if needed — just rename for now
                    if (move_uploaded_file($cover_tmp, $cover_dest)) {
                        $cover_path_db = 'uploads/covers/' . $cover_filename;
                    }
                }
            }

            // ── Insert DB row ─────────────────────────────────
            $t  = $conn->real_escape_string($title);
            $a  = $conn->real_escape_string($artist);
            $g  = $conn->real_escape_string($genre);
            $m  = $conn->real_escape_string($mood_tag);
            $mt = $conn->real_escape_string($manual_tags);
            $fp = 'uploads/songs/' . $song_filename;
            $cp = $cover_path_db ? $conn->real_escape_string($cover_path_db) : null;
            $d  = $duration ? $duration : 'NULL';
            $cp_sql = $cp ? "'$cp'" : 'NULL';

            $conn->query(
                "INSERT INTO songs (title, artist, genre, mood_tag, manual_tags, is_explicit, file_path, cover_path, duration)
                 VALUES ('$t', '$a', '$g', '$m', '$mt', $is_explicit, '$fp', $cp_sql, $d)"
            );

            if ($conn->affected_rows === 1) {
                header('Location: /groovekut/admin/dashboard.php?added=1');
                exit;
            } else {
                $error = 'DB insert failed: ' . $conn->error;
                // Cleanup uploaded file if DB failed
                if (file_exists($song_dest)) unlink($song_dest);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add Song — GrooveKut Admin</title>
  <link rel="apple-touch-icon" sizes="180x180" href="/groovekut/assets/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/groovekut/assets/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/groovekut/assets/favicon/favicon-16x16.png">
  <link rel="manifest" href="/groovekut/assets/favicon/site.webmanifest">
  <link rel="stylesheet" href="/groovekut/assets/fonts/remixicon.css" />
  <link rel="stylesheet" href="/groovekut/assets/css/style.css" />
  <link rel="stylesheet" href="/groovekut/assets/css/admin.css"/>
</head>
<body class="admin-body">

<nav class="admin-nav">
  <div class="admin-nav-brand">
    <span class="logo-groove">Groove</span><span class="logo-kut">Kut</span>
    <span class="admin-badge">Admin</span>
  </div>
  <div class="admin-nav-links">
    <a href="/groovekut/admin/dashboard.php" class="admin-nav-link">
      <i class="ri-dashboard-line"></i> Dashboard
    </a>
    <a href="/groovekut/admin/add_song.php" class="admin-nav-link active">
      <i class="ri-add-circle-line"></i> Add Song
    </a>
    <a href="/groovekut/admin/logout.php" class="admin-nav-link danger">
      <i class="ri-logout-box-r-line"></i> Logout
    </a>
  </div>
</nav>

<div class="admin-wrap">
  <div class="admin-form-card">
    <h2 class="admin-section-title"><i class="ri-add-circle-line"></i> Add New Song</h2>
    <p style="color:var(--text-2);font-size:13px;margin-bottom:24px;">
      Filename will be auto-generated as <code><?= str_pad('N', 3, '0', STR_PAD_LEFT) ?>-artist-title.mp3</code>
    </p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="admin-form">

      <div class="admin-form-row">
        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                 placeholder="Full song title" required/>
        </div>
        <div class="form-group">
          <label>Artist *</label>
          <input type="text" name="artist" value="<?= htmlspecialchars($_POST['artist'] ?? '') ?>"
                 placeholder="Artist name(s) — comma separate multiple"/>
        </div>
      </div>

      <div class="admin-form-row">
        <div class="form-group">
          <label>Genre *</label>
          <select name="genre" required>
            <option value="">Select genre</option>
            <?php foreach ($valid_genres as $g): ?>
              <option value="<?= $g ?>" <?= (($_POST['genre'] ?? '') === $g) ? 'selected' : '' ?>>
                <?= $g ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Mood *</label>
          <select name="mood_tag" required>
            <option value="">Select mood</option>
            <?php foreach ($valid_moods as $m): ?>
              <option value="<?= $m ?>" <?= (($_POST['mood_tag'] ?? '') === $m) ? 'selected' : '' ?>>
                <?= ucfirst($m) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="admin-form-row">
        <div class="form-group">
          <label>Manual Tags</label>
          <input type="text" name="manual_tags"
                 value="<?= htmlspecialchars($_POST['manual_tags'] ?? '') ?>"
                 placeholder="e.g. workout,energy,party"/>
        </div>
        <div class="form-group">
          <label>Duration (seconds)</label>
          <input type="number" name="duration"
                 value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>"
                 placeholder="e.g. 225 for 3:45"/>
        </div>
      </div>

      <div class="form-group">
        <label class="checkbox-label">
          <input type="checkbox" name="is_explicit" value="1"
                 <?= isset($_POST['is_explicit']) ? 'checked' : '' ?>/>
          Explicit content (E)
        </label>
      </div>

      <div class="admin-form-row">
        <div class="form-group">
          <label>MP3 File *</label>
          <div class="file-upload-area" id="song-upload">
            <i class="ri-music-2-line"></i>
            <p>Click or drag MP3 here</p>
            <input type="file" name="song_file" accept=".mp3,audio/mpeg" required
                   onchange="showFileName(this, 'song-name')"/>
          </div>
          <p class="file-name" id="song-name"></p>
        </div>
        <div class="form-group">
          <label>Cover Image (JPG/PNG)</label>
          <div class="file-upload-area" id="cover-upload">
            <i class="ri-image-line"></i>
            <p>Click or drag cover here</p>
            <input type="file" name="cover_file" accept=".jpg,.jpeg,.png,.webp,image/*"
                   onchange="showFileName(this, 'cover-name'); previewCover(this)"/>
          </div>
          <p class="file-name" id="cover-name"></p>
          <img id="cover-preview" src="" alt="" style="display:none;width:80px;height:80px;object-fit:cover;border-radius:8px;margin-top:8px;"/>
        </div>
      </div>

      <div style="display:flex;gap:12px;margin-top:8px;">
        <button type="submit" class="btn-primary">
          <i class="ri-upload-cloud-line"></i> Upload & Add Song
        </button>
        <a href="/groovekut/admin/dashboard.php" class="btn-ghost">Cancel</a>
      </div>

    </form>
  </div>
</div>

<script>
function showFileName(input, targetId) {
  const el = document.getElementById(targetId);
  el.textContent = input.files[0] ? input.files[0].name : '';
}

function previewCover(input) {
  const preview = document.getElementById('cover-preview');
  if (input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
</body>
</html>
