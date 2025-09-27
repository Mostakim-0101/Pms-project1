<?php
// register.php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/auth.php';

// Derive base path (works if app folder name changes, e.g., /pms, /pms5)
$BASE = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($BASE === '') $BASE = '/';

// If already logged in, bounce to home
if ($auth->isLoggedIn()) {
    header('Location: ' . $BASE . '/index.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error  = '';
$fields = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Read + sanitize
        $fields['full_name'] = trim((string)($_POST['full_name'] ?? ''));
        $fields['email']     = strtolower(trim((string)($_POST['email'] ?? '')));
        $password            = (string)($_POST['password'] ?? '');
        $confirm             = (string)($_POST['confirm_password'] ?? '');

        // Validate
        if ($fields['full_name'] === '' || $fields['email'] === '' || $password === '' || $confirm === '') {
            $error = 'All fields are required.';
        } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            // Attempt registration (expects ['success'=>bool, 'message'=>string] from Auth)
            try {
                $res = $auth->registerPatient($fields['full_name'], $fields['email'], $password);
                if (!empty($res['success'])) {
                    // Optionally unset CSRF so refresh can’t repost
                    unset($_SESSION['csrf']);
                    header('Location: ' . $BASE . '/login.php?registered=1');
                    exit;
                }
                // Normalize common errors (e.g., duplicate email unique key)
                $msg = (string)($res['message'] ?? '');
                if ($msg === '' && method_exists($auth, 'lastError')) {
                    $msg = (string)$auth->lastError();
                }
                if ($msg === '' || stripos($msg, 'duplicate') !== false) {
                    $msg = 'This email is already registered. Try signing in.';
                }
                $error = $msg;
            } catch (Throwable $e) {
                // Don’t leak internals to the user
                $error = 'Registration failed due to a server error. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Account - Pakenham Hospital</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($BASE) ?>/css/modern-style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4RhisW0v4V0Lh…"
        crossorigin="anonymous" referrerpolicy="no-referrer">
  <style>
    .landing-container{min-height:100vh;display:grid;place-items:center;background:var(--gray-50)}
  </style>
</head>
<body>
  <div class="landing-container">
    <div class="card" style="max-width:480px;margin:0 auto;">
      <div class="text-center mb-6">
        <!-- Adjust path if your logo lives elsewhere -->
        <img src="<?= htmlspecialchars($BASE) ?>/assets/images/logo.jpeg" alt="Pakenham Hospital"
             style="width:60px;height:60px;margin:0 auto 1rem;object-fit:contain;">
        <h1 style="color:var(--primary-800);margin-bottom:.5rem;">Create Account</h1>
        <p style="color:var(--gray-600);">Register as a patient</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error" role="alert" aria-live="polite">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="form-container" style="max-width:none;padding:0;" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

        <div class="form-group">
          <label class="form-label" for="full_name">Full Name</label>
          <div class="input-group">
            <input id="full_name" type="text" name="full_name" class="form-input"
                   placeholder="Your full name" value="<?= htmlspecialchars($fields['full_name']) ?>"
                   autocomplete="name" required>
            <div class="input-icon"><i class="fas fa-user"></i></div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <div class="input-group">
            <input id="email" type="email" name="email" class="form-input"
                   placeholder="you@example.com" value="<?= htmlspecialchars($fields['email']) ?>"
                   autocomplete="email" required>
            <div class="input-icon"><i class="fas fa-envelope"></i></div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="input-group">
            <input id="password" type="password" name="password" class="form-input"
                   placeholder="Create a password" minlength="8"
                   autocomplete="new-password" required>
            <div class="input-icon"><i class="fas fa-lock"></i></div>
          </div>
          <div class="form-help">Use at least 8 characters.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm Password</label>
          <div class="input-group">
            <input id="confirm_password" type="password" name="confirm_password" class="form-input"
                   placeholder="Repeat your password" minlength="8"
                   autocomplete="new-password" required>
            <div class="input-icon"><i class="fas fa-lock"></i></div>
          </div>
        </div>

        <button class="btn btn-primary btn-full" type="submit">
          <i class="fas fa-user-plus"></i> Create Account
        </button>
      </form>

      <div class="text-center mt-6" style="padding-top:1.5rem;border-top:1px solid var(--gray-200);">
        <a href="<?= htmlspecialchars($BASE) ?>/login.php" style="color:var(--primary-600);font-weight:600;">
          Already have an account? Sign in
        </a>
        <div class="mt-2">
          <a href="<?= htmlspecialchars($BASE) ?>/index.php" style="color:var(--gray-500);font-size:.875rem;">
            <i class="fas fa-arrow-left"></i> Back to Home
          </a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
