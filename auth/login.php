<?php
// ============================================================
// GrooveKut — Login
// File: auth/login.php
// ============================================================

require_once __DIR__ . '/../includes/session_helper.php';

if (is_logged_in()) {
    header('Location: /groovekut/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identifier  = trim($_POST['identifier'] ?? '');   // username OR email
    $password    = $_POST['password']        ?? '';
    $remember_me = isset($_POST['remember_me']);

    if (!$identifier || !$password) {
        $error = 'Please enter your username/email and password.';

    } else {
        $i = $conn->real_escape_string($identifier);

        $result = $conn->query(
            "SELECT id, username, password_hash, preferred_mood, onboarding_done
             FROM users
             WHERE username = '$i' OR email = '$i'
             LIMIT 1"
        );

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_hash'])) {

                // ── Set session ───────────────────────────
                $_SESSION['user_id']         = $user['id'];
                $_SESSION['username']        = $user['username'];
                $_SESSION['current_mood']    = $user['preferred_mood'] ?? 'chill';
                $_SESSION['onboarding_done'] = $user['onboarding_done'];

                // ── Remember me cookie (30 days) ──────────
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $t     = $conn->real_escape_string($token);
                    $id    = $user['id'];

                    $conn->query(
                        "UPDATE users SET remember_token = '$t' WHERE id = $id"
                    );

                    setcookie(
                        'remember_token',
                        $token,
                        [
                            'expires'  => time() + (30 * 24 * 60 * 60),
                            'path'     => '/',
                            'httponly' => true,
                            'samesite' => 'Lax',
                        ]
                    );
                }

                header('Location: /groovekut/dashboard.php');
                exit;

            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'No account found with that username or email.';
        }
    }
}

$page_title = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-brand">
      <span class="logo-groove">Groove</span><span class="logo-kut">Kut</span>
    </div>
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-sub">Log in and pick up where you left off.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">Account created! Log in below.</div>
    <?php endif; ?>

    <form method="POST" action="" class="auth-form">

      <div class="form-group">
        <label for="identifier">Username or Email</label>
        <input type="text" id="identifier" name="identifier"
               value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
               placeholder="username or email" autocomplete="username" required/>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="your password" autocomplete="current-password" required/>
      </div>

      <div class="form-check">
        <input type="checkbox" id="remember_me" name="remember_me"
               <?= isset($_POST['remember_me']) ? 'checked' : '' ?>/>
        <label for="remember_me">Remember me for 30 days</label>
      </div>

      <button type="submit" class="btn-primary btn-full">Log In</button>

    </form>

    <p class="auth-switch">Don't have an account? <a href="/groovekut/auth/register.php">Sign up</a></p>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
