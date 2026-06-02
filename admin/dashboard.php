<?php
// ============================================================
// GrooveKut — Admin Dashboard
// File: admin/dashboard.php
// ============================================================

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /groovekut/admin/index.php');
    exit;
}

require_once __DIR__ . '/../includes/db_connect.php';

// ── Handle delete ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    // Get file paths before deleting
    $fres = $conn->query("SELECT file_path, cover_path FROM songs WHERE id = $del_id LIMIT 1");
    if ($fres && $fres->num_rows > 0) {
        $fpaths = $fres->fetch_assoc();
        // Remove physical files
        $song_file  = __DIR__ . '/../' . $fpaths['file_path'];
        $cover_file = __DIR__ . '/../' . ($fpaths['cover_path'] ?? '');
        if (file_exists($song_file))  unlink($song_file);
        if (file_exists($cover_file)) unlink($cover_file);
    }
    // Delete from DB (cascades to play_history, liked_songs)
    $conn->query("DELETE FROM songs WHERE id = $del_id");
    header('Location: /groovekut/admin/dashboard.php?deleted=1');
    exit;
}

// ── Stats ─────────────────────────────────────────────────────
$total_songs = (int)$conn->query("SELECT COUNT(*) AS c FROM songs")->fetch_assoc()['c'];
$total_users = (int)$conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$total_plays = (int)$conn->query("SELECT SUM(play_count) AS c FROM play_history")->fetch_assoc()['c'];
$total_likes = (int)$conn->query("SELECT COUNT(*) AS c FROM liked_songs")->fetch_assoc()['c'];

// ── Top 10 played songs ───────────────────────────────────────
$top_songs = [];
$res = $conn->query(
    "SELECT s.id, s.title, s.artist, s.genre, SUM(ph.play_count) AS plays
     FROM play_history ph JOIN songs s ON s.id = ph.song_id
     GROUP BY s.id ORDER BY plays DESC LIMIT 10"
);
while ($row = $res->fetch_assoc()) $top_songs[] = $row;

// ── Song list (paginated) ─────────────────────────────────────
$page     = max(1, intval($_GET['page'] ?? 1));
$per_page = 25;
$offset   = ($page - 1) * $per_page;
$search   = trim($_GET['s'] ?? '');

$where = '';
if ($search) {
    $s_esc = $conn->real_escape_string($search);
    $where = "WHERE title LIKE '%$s_esc%' OR artist LIKE '%$s_esc%'";
}

$total_filtered = (int)$conn->query("SELECT COUNT(*) AS c FROM songs $where")->fetch_assoc()['c'];
$total_pages    = ceil($total_filtered / $per_page);

$songs = [];
$res = $conn->query("SELECT * FROM songs $where ORDER BY id DESC LIMIT $per_page OFFSET $offset");
while ($row = $res->fetch_assoc()) $songs[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard — GrooveKut</title>
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
    <a href="/groovekut/admin/dashboard.php" class="admin-nav-link active">
      <i class="ri-dashboard-line"></i> Dashboard
    </a>
    <a href="/groovekut/admin/add_song.php" class="admin-nav-link">
      <i class="ri-add-circle-line"></i> Add Song
    </a>
    <a href="/groovekut/admin/logout.php" class="admin-nav-link danger">
      <i class="ri-logout-box-r-line"></i> Logout
    </a>
  </div>
</nav>

<div class="admin-wrap">

  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Song deleted successfully.</div>
  <?php endif; ?>
  <?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">Song added successfully.</div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="admin-stats">
    <div class="admin-stat"><span class="admin-stat-num"><?= $total_songs ?></span><span class="admin-stat-label">Songs</span></div>
    <div class="admin-stat"><span class="admin-stat-num"><?= $total_users ?></span><span class="admin-stat-label">Users</span></div>
    <div class="admin-stat"><span class="admin-stat-num"><?= number_format($total_plays) ?></span><span class="admin-stat-label">Total Plays</span></div>
    <div class="admin-stat"><span class="admin-stat-num"><?= $total_likes ?></span><span class="admin-stat-label">Likes</span></div>
  </div>

  <!-- Top played -->
  <?php if (!empty($top_songs)): ?>
  <div class="admin-section">
    <h2 class="admin-section-title"><i class="ri-fire-line"></i> Top 10 Most Played</h2>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Title</th><th>Artist</th><th>Genre</th><th>Plays</th></tr></thead>
        <tbody>
          <?php foreach ($top_songs as $i => $s): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($s['title']) ?></td>
              <td><?= htmlspecialchars($s['artist']) ?></td>
              <td><?= htmlspecialchars($s['genre']) ?></td>
              <td><strong><?= $s['plays'] ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Song list -->
  <div class="admin-section">
    <div class="admin-section-head">
      <h2 class="admin-section-title"><i class="ri-music-2-line"></i> Song Library</h2>
      <div class="admin-search-wrap">
        <form method="GET" style="display:flex;gap:8px;">
          <input type="text" name="s" value="<?= htmlspecialchars($search) ?>"
                 placeholder="Search title or artist..." class="admin-search-input"/>
          <button type="submit" class="btn-primary">Search</button>
          <?php if ($search): ?>
            <a href="/groovekut/admin/dashboard.php" class="btn-ghost">Clear</a>
          <?php endif; ?>
        </form>
        <a href="/groovekut/admin/add_song.php" class="btn-primary">
          <i class="ri-add-line"></i> Add Song
        </a>
      </div>
    </div>

    <p class="admin-count"><?= $total_filtered ?> songs<?= $search ? ' matching "'.htmlspecialchars($search).'"' : '' ?></p>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Cover</th>
            <th>Title</th>
            <th>Artist</th>
            <th>Genre</th>
            <th>Mood</th>
            <th>E</th>
            <th>Duration</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($songs as $s): ?>
            <tr>
              <td><?= $s['id'] ?></td>
              <td>
                <?php if ($s['cover_path']): ?>
                  <img src="/groovekut/<?= htmlspecialchars($s['cover_path']) ?>"
                       class="admin-thumb" alt="cover"/>
                <?php else: ?>
                  <div class="admin-thumb-ph">🎵</div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($s['title']) ?></td>
              <td><?= htmlspecialchars($s['artist']) ?></td>
              <td><?= htmlspecialchars($s['genre']) ?></td>
              <td><span class="tag-mood mood-<?= $s['mood_tag'] ?>"><?= ucfirst($s['mood_tag']) ?></span></td>
              <td><?= $s['is_explicit'] ? '<span class="tag-explicit sm">E</span>' : '—' ?></td>
              <td>
                <?php if ($s['duration']): ?>
                  <?= floor($s['duration']/60).':'.str_pad($s['duration']%60,2,'0',STR_PAD_LEFT) ?>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td>
                <div class="admin-actions">
                  <a href="/groovekut/player.php?id=<?= $s['id'] ?>"
                     class="admin-action-btn" target="_blank" title="Preview">
                    <i class="ri-play-circle-line"></i>
                  </a>
                  <form method="POST" style="display:inline;"
                        onsubmit="return confirm('Delete \'<?= addslashes($s['title']) ?>\'? This cannot be undone.')">
                    <input type="hidden" name="delete_id" value="<?= $s['id'] ?>"/>
                    <button type="submit" class="admin-action-btn danger" title="Delete">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <div class="admin-pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?= $i ?>&s=<?= urlencode($search) ?>"
             class="page-btn <?= $i === $page ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
