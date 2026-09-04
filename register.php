<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (current_user_id()) { header('Location: dashboard.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);

        session_regenerate_id(true); // prevent session fixation
        $_SESSION['user_id'] = (int)$pdo->lastInsertId();
        flash_set('success', 'Welcome to YONZON CLAIM — your registry is ready.');
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
<title>Create an account — YONZON CLAIM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,340;0,9..144,480;0,9..144,600;1,9..144,460&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-shell">
  <a class="auth-mark" href="index.php">
    <svg viewBox="0 0 40 40" fill="none"><path d="M20 3L36 20L20 37L4 20L20 3Z" stroke="currentColor" stroke-width="1"/><path d="M12 12L28 28M28 12L12 28" stroke="currentColor" stroke-width="1"/><path d="M20 3V37" stroke="currentColor" stroke-width="1"/></svg>
    <span>YONZON CLAIM</span>
  </a>

  <div class="auth-card">
    <div class="kicker">Filing No. New</div>
    <h1 class="auth-title">Open a registry account.</h1>
    <p class="auth-sub">Takes under a minute. No credit card, no verification wait.</p>

    <?php if ($errors): ?>
      <div class="auth-error">
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <label class="field">
        <span>Full name</span>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required maxlength="100">
      </label>
      <label class="field">
        <span>Email</span>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required maxlength="150">
      </label>
      <label class="field">
        <span>Password</span>
        <input type="password" name="password" required minlength="8">
      </label>
      <label class="field">
        <span>Confirm password</span>
        <input type="password" name="confirm" required minlength="8">
      </label>
      <button type="submit" class="btn btn-primary auth-submit">Create account</button>
    </form>

    <p class="auth-switch">Already registered? <a href="login.php">Log in</a></p>
  </div>
</div>

</body>
</html>
