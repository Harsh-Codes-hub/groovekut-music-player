<?php
// includes/header.php
// Usage: require_once at top of every page AFTER session_helper
// Pass $page_title before including
$page_title = $page_title ?? 'GrooveKut';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?> — GrooveKut</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="/groovekut/assets/css/style.css" />
</head>

<body>

  <nav class="navbar">
    <a href="/groovekut/dashboard.php" class="nav-logo">
      <span class="logo-groove">Groove</span><span class="logo-kut">Kut</span>
    </a>

    <div class="nav-links">
      <?php if (is_logged_in()): ?>
        <a href="/groovekut/dashboard.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : '' ?>"><i class="ri-home-5-line"></i> Home</a>
        <a href="/groovekut/library.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'library.php')   ? 'active' : '' ?>"><i class="ri-bar-chart-box-fill"></i> Library</a>
        <a href="/groovekut/search.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'search.php')    ? 'active' : '' ?>"><i class="ri-search-line"></i> Search</a>
        <a href="/groovekut/profile.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'profile.php')   ? 'active' : '' ?>"><i class="ri-user-line"></i> Profile</a>
        <a href="/groovekut/auth/logout.php" class="nav-link nav-logout"><i class="ri-logout-box-r-line"></i> Logout</a>
      <?php else: ?>
        <a href="/groovekut/auth/login.php" class="nav-link">Login</a>
        <a href="/groovekut/auth/register.php" class="nav-btn">Sign Up</a>
      <?php endif; ?>
    </div>
  </nav>

  <main class="main-content">