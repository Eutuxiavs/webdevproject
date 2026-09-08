<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$itemId = (int)($_GET['item'] ?? $_POST['item'] ?? 0);

$stmt = $pdo->prepare('SELECT i.*, u.name AS owner_name FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = ?');
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    flash_set('error', 'That item was not found.');
    header('Location: browse.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $reason = clean_text($_POST['reason'] ?? '');

    if ($reason === '' || mb_strlen($reason) > 500) {
        $errors[] = 'Describe the issue (max 500 characters).';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO reports (reporter_id, reported_user_id, reported_item_id, reason) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$user['id'], $item['user_id'], $item['id'], $reason]);
        flash_set('success', 'Thanks — this has been reported for review.');
        header('Location: browse.php');
        exit;
    }
}
$pageTitle = 'Report listing — YONZON CLAIM';
$activeNav = '';
require __DIR__ . '/includes/header-dash.php';
?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">Listed by <?= e($item['owner_name']) ?></div>
      <h1 class="dash-title">Report &ldquo;<?= e($item['name']) ?>&rdquo;</h1>
    </div>
    <a class="btn btn-ghost" href="browse.php">&larr; Back to marketplace</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" class="form-card">
    <?= csrf_field() ?>
    <input type="hidden" name="item" value="<?= (int)$item['id'] ?>">

    <label class="field">
      <span>What's wrong with this listing?</span>
      <textarea name="reason" rows="4" maxlength="500" placeholder="e.g. this looks like a stolen item, the seller isn't responding, misleading photos…" required></textarea>
    </label>

    <button type="submit" class="btn btn-primary form-submit">Submit report</button>
  </form>
</main>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>