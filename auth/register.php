<?php
// ============================================================
// GrooveKut — Register
// File: auth/register.php
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';

// Already logged in → go to dashboard
if (is_logged_in()) {
    header('Location: /groovekut/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    // ── Basic validation ──────────────────────────────────
    if (!$username || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';

    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';

    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';

    } else {
        // ── Check username/email taken ────────────────────
        $u = $conn->real_escape_string($username);
        $e = $conn->real_escape_string($email);

        $check = $conn->query(
            "SELECT id FROM users
             WHERE username = '$u' OR email = '$e'
             LIMIT 1"
        );

        if ($check && $check->num_rows > 0) {
            $error = 'Username or email is already taken.';
        } else {
            // ── Insert new user ───────────────────────────
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $conn->query(
                "INSERT INTO users (username, email, password_hash, onboarding_done)
                 VALUES ('$u', '$e', '$hash', 0)"
            );

            if ($conn->affected_rows === 1) {
                $new_id = $conn->insert_id;

                // Auto-login after register
                $_SESSION['user_id']         = $new_id;
                $_SESSION['username']        = $username;
                $_SESSION['current_mood']    = 'chill';
                $_SESSION['onboarding_done'] = 0;

                // Redirect to dashboard — onboarding modal will fire there
                header('Location: /groovekut/dashboard.php');
                exit;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

$page_title = 'Create Account';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-brand">
      <span class="logo-groove">Groove</span><span class="logo-kut">Kut</span>
    </div>
    <h1 class="auth-title">Create your account</h1>
    <p class="auth-sub">Start listening in seconds.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="auth-form">

      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="your_name" autocomplete="username" required/>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@email.com" autocomplete="email" required/>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="min. 6 characters" autocomplete="new-password" required/>
      </div>

      <div class="form-group">
        <label for="confirm">Confirm Password</label>
        <input type="password" id="confirm" name="confirm"
               placeholder="repeat password" autocomplete="new-password" required/>
      </div>

      <button type="submit" class="btn-primary btn-full">Create Account</button>

    </form>

    <p class="auth-switch">Already have an account? <a href="/groovekut/auth/login.php">Log in</a></p>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
