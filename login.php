<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user_id()) { header('Location: dashboard.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        // Same message either way — don't reveal which part was wrong.
        $errors[] = 'Incorrect email or password.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in — YONZON CLAIM</title>
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
    <div class="kicker">Filed under</div>
    <h1 class="auth-title">Welcome back.</h1>
    <p class="auth-sub">Log in to see your registry.</p>

    <?php if ($errors): ?>
      <div class="auth-error">
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <label class="field">
        <span>Email</span>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
      </label>
      <label class="field">
        <span>Password</span>
        <input type="password" name="password" required>
      </label>
      <button type="submit" class="btn btn-primary auth-submit">Log in</button>
    </form>

    <p class="auth-switch">New here? <a href="register.php">Create an account</a></p>
  </div>
</div>

</body>
</html>