<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user_id()) { header('Location: dashboard.php'); exit; }

$errors = [];
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show the same message whether or not the email exists —
    // otherwise this becomes a way to find out which emails are registered.
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        );
        $stmt->execute([$user['id'], $token]);
        $resetLink = 'reset-password.php?token=' . $token;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot password — YONZON CLAIM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,340;0,9..144,480;0,9..144,600;1,9..144,460&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
</head>
<body class="auth-body">

<div class="auth-shell">
  <a class="auth-mark" href="index.php">
    <?php brand_mark(24); ?>
    <span>YONZON CLAIM</span>
  </a>

  <div class="auth-card">
    <div class="kicker">Account recovery</div>
    <h1 class="auth-title">Reset your password.</h1>
    <p class="auth-sub">Enter the email on your account.</p>

    <?php if ($resetLink): ?>
      <div class="alert alert-success" style="margin-bottom:20px;">
        If that email exists, a reset link has been generated. <strong>This project has no email server
        configured</strong>, so here it is directly instead of being emailed:<br>
        <a href="<?= e($resetLink) ?>" style="color:var(--accent-l); word-break:break-all;"><?= e($resetLink) ?></a>
        <br><small style="color:var(--paper-faint);">Expires in 1 hour. In a real deployment this link
        would be sent to the email address instead of shown here.</small>
      </div>
    <?php else: ?>
      <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">If that email exists on an account, a reset link has been generated.</div>
      <?php endif; ?>

      <form method="post" novalidate class="js-validate">
        <?= csrf_field() ?>
        <label class="field">
          <span>Email</span>
          <input type="email" name="email" required autofocus>
        </label>
        <button type="submit" class="btn btn-primary auth-submit">Send reset link</button>
      </form>
    <?php endif; ?>

    <p class="auth-switch"><a href="login.php">Back to log in</a></p>
  </div>
</div>

<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>