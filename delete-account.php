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
$pageTitle = 'Delete account — YONZON CLAIM';
$activeNav = '';
require __DIR__ . '/includes/header-dash.php';
?>

<div style="max-width:520px;">
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
</div>

<?php $showChatWidget = false; require __DIR__ . '/includes/footer-dash.php'; ?>