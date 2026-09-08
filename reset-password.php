<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];

$stmt = $pdo->prepare(
    'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()'
);
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $errors[] = 'This reset link is invalid or has expired. Request a new one.';
}

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset['id']]);
        reset_login_attempts($pdo, (int)$reset['user_id']);

        flash_set('success', 'Password updated. Log in with your new password.');
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset password — YONZON CLAIM</title>
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
    <h1 class="auth-title">Choose a new password.</h1>

    <?php if ($errors): ?>
      <div class="auth-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if ($reset): ?>
      <form method="post" novalidate class="js-validate">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label class="field">
          <span>New password</span>
          <input type="password" name="password" required minlength="8">
        </label>
        <label class="field">
          <span>Confirm new password</span>
          <input type="password" name="confirm" required minlength="8">
        </label>
        <button type="submit" class="btn btn-primary auth-submit">Update password</button>
      </form>
    <?php else: ?>
      <p class="auth-switch"><a href="forgot-password.php">Request a new reset link</a></p>
    <?php endif; ?>
  </div>
</div>

<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>