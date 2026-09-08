<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        $errors[] = 'Incorrect password.';
    } else {
        // Items, offers, conversations, messages, reviews, blocks all cascade
        // via ON DELETE CASCADE foreign keys defined in database.sql.
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
        $_SESSION = [];
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete account — YONZON CLAIM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,340;0,9..144,480;0,9..144,600;1,9..144,460&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
</head>
<body class="dash-body">

<div class="letterhead-top dash-letterhead">
  <div class="dash-wrap">
    <div class="lh-main" style="padding:18px 0;">
      <a class="mark" href="dashboard.php">
        <?php brand_mark(34); ?>
        <div class="mark-word"><div class="a">YONZON</div><div class="b">Claim Registry</div></div>
      </a>
      <div class="dash-user">
        <span><?= e($user['name']) ?></span>
        <a class="btn btn-ghost" href="logout.php">Log out</a>
      </div>
    </div>
  </div>
</div>

<main class="dash-wrap dash-main" style="max-width:520px;">
  <h1 class="dash-title" style="margin-bottom:20px;">Delete your account</h1>

  <div class="alert alert-error">
    This permanently deletes your account, every item you've registered, all your listings, offers,
    messages, and reviews. <strong>This can't be undone.</strong>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" class="form-card" data-confirm="This is permanent and cannot be undone. Are you absolutely sure?" data-confirm-title="Delete your account?">
    <?= csrf_field() ?>
    <label class="field">
      <span>Confirm your password</span>
      <input type="password" name="password" required autofocus>
    </label>
    <button type="submit" class="btn btn-primary form-submit" style="background:#e5484d;">Permanently delete my account</button>
  </form>

  <p class="auth-switch"><a href="profile.php">Cancel, go back</a></p>
</main>

<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>
