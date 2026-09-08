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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report listing — YONZON CLAIM</title>
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
      <nav class="dash-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="browse.php">Marketplace</a>
        <a href="offers.php">Offers<?= pending_offer_badge($pdo, $user['id']) ?></a>
        <a href="profile.php">Profile</a>
      </nav>
      <div class="dash-user">
        <span><?= e($user['name']) ?></span>
        <a class="btn btn-ghost" href="logout.php">Log out</a>
      </div>
    </div>
  </div>
</div>

<main class="dash-wrap dash-main">
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

<?php require __DIR__ . '/includes/chat-widget.php'; ?>
<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>
