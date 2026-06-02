<?php
// ============================================================
// GrooveKut — Admin Login
// File: admin/index.php
// Separate from user auth — hardcoded credentials
// ============================================================

session_start();

// ── Hardcoded admin credentials ───────────────────────────────
// Change these before deployment
define('ADMIN_USER', 'groovekut_admin');
define('ADMIN_PASS', 'gk@admin2024');

// Already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
  header('Location: /groovekut/admin/dashboard.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = trim($_POST['username'] ?? '');
  $pass = $_POST['password'] ?? '';

  if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user']      = $user;
    header('Location: /groovekut/admin/dashboard.php');
    exit;
  } else {
    $error = 'Invalid credentials.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — GrooveKut</title>
  <link rel="apple-touch-icon" sizes="180x180" href="/groovekut/assets/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/groovekut/assets/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/groovekut/assets/favicon/favicon-16x16.png">
  <link rel="manifest" href="/groovekut/assets/favicon/site.webmanifest">
  <link rel="stylesheet" href="/groovekut/assets/fonts/remixicon.css" />
  <link rel="stylesheet" href="/groovekut/assets/css/style.css" />
  <link rel="stylesheet" href="/groovekut/assets/css/admin.css"/>
</head>

<body>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-brand">
        <span class="logo-groove">Groove</span><span class="logo-kut">Kut</span>
        <span class="admin-badge">Admin</span>
      </div>
      <h1 class="auth-title">Admin Panel</h1>
      <p class="auth-sub">Restricted access only.</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="admin username" required autofocus />
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="admin password" required />
        </div>
        <button type="submit" class="btn-primary btn-full">
          <i class="ri-shield-keyhole-line"></i> Login
        </button>
      </form>

      <p style="text-align:center;margin-top:20px;">
        <a href="/groovekut/" style="font-size:13px;color:var(--text-3);">← Back to GrooveKut</a>
      </p>
    </div>
  </div>
</body>

</html>