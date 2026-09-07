<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$itemId = (int)($_GET['item'] ?? $_POST['item'] ?? 0);

$stmt = $pdo->prepare('SELECT i.*, u.name AS owner_name FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = ? AND i.status = "for_sale"');
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    flash_set('error', 'That listing is no longer available for offers.');
    header('Location: browse.php');
    exit;
}
if ((int)$item['user_id'] === $user['id']) {
    flash_set('error', "You can't make an offer on your own item.");
    header('Location: browse.php');
    exit;
}

// One pending offer per buyer per item — reuse it instead of duplicating.
$stmt = $pdo->prepare('SELECT id FROM offers WHERE item_id = ? AND buyer_id = ? AND status = "pending"');
$stmt->execute([$item['id'], $user['id']]);
if ($stmt->fetch()) {
    flash_set('success', 'You already have a pending offer on this item.');
    header('Location: offers.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $offerPrice = trim($_POST['offer_price'] ?? '');
    $message    = trim($_POST['message'] ?? '');

    if (!is_numeric($offerPrice) || (float)$offerPrice <= 0) {
        $errors[] = 'Enter a valid offer amount.';
    }
    if (mb_strlen($message) > 500) {
        $errors[] = 'Message is too long (max 500 characters).';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO offers (item_id, buyer_id, seller_id, offer_price, message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$item['id'], $user['id'], $item['user_id'], (float)$offerPrice, $message ?: null]);

        flash_set('success', "Offer sent to {$item['owner_name']}. You'll be notified when they respond.");
        header('Location: offers.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Make an offer — YONZON CLAIM</title>
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
        <a href="offers.php">Offers</a>
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
      <h1 class="dash-title">Offer on &ldquo;<?= e($item['name']) ?>&rdquo;</h1>
    </div>
    <a class="btn btn-ghost" href="browse.php">&larr; Back to marketplace</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <div class="offer-item-preview">
    <div class="offer-item-photo">
      <?php if ($item['photo_path']): ?>
        <img src="<?= e(UPLOAD_URL . $item['photo_path']) ?>" alt="<?= e($item['name']) ?>">
      <?php else: ?>
        <?= yz_icon(category_icon_key($item['category'])) ?>
      <?php endif; ?>
    </div>
    <div>
      <div class="reg-cat"><?= e($item['category']) ?></div>
      <div class="reg-name" style="font-size:20px;"><?= e($item['name']) ?></div>
      <div class="dash-summary" style="margin-top:8px;">Asking price: <strong style="color:var(--accent-l);">$<?= number_format((float)$item['price'], 2) ?></strong></div>
    </div>
  </div>

  <form method="post" class="form-card">
    <?= csrf_field() ?>

    <label class="field">
      <span>Your offer (USD)</span>
      <input type="number" name="offer_price" step="0.01" min="0.01" value="<?= e($_POST['offer_price'] ?? number_format((float)$item['price'], 2, '.', '')) ?>" required>
    </label>

    <label class="field">
      <span>Message to seller <em>(optional)</em></span>
      <textarea name="message" rows="3" maxlength="500" placeholder="e.g. Can you meet at..."><?= e($_POST['message'] ?? '') ?></textarea>
    </label>

    <div class="safety-note">
      <strong>How this stays safe:</strong> the seller must accept your offer before anything happens.
      After they accept, ownership only transfers once <em>both of you</em> separately confirm the
      handover went through — neither side can force it alone.
    </div>

    <button type="submit" class="btn btn-primary form-submit">Send offer</button>
  </form>
</main>

<?php require __DIR__ . '/includes/chat-widget.php'; ?>
<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>
