<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

$stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();

$counts = ['owned' => 0, 'for_sale' => 0, 'lost' => 0, 'warranty' => 0, 'sold' => 0];
foreach ($items as $it) { $counts[$it['status']] = ($counts[$it['status']] ?? 0) + 1; }

$success = flash_get('success');
$error   = flash_get('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — YONZON CLAIM</title>
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
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="browse.php">Marketplace</a>
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

  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">
        <?= count($items) ?> filed &middot;
        <?= $counts['for_sale'] ?> for sale &middot;
        <?= $counts['lost'] ?> lost &middot;
        <?= $counts['warranty'] ?> under warranty
      </div>
      <h1 class="dash-title">Your registry.</h1>
    </div>
    <div class="dash-actions">
      <a class="btn btn-primary" href="claim-add.php">+ Register an item</a>
    </div>
  </div>

  <section class="dash-claims">
    <?php if (!$items): ?>
      <div class="dash-empty">
        <p>Nothing filed yet.</p>
        <a class="btn btn-ghost" href="claim-add.php">Register your first item</a>
      </div>
    <?php else: ?>
      <div class="inv-grid">
        <?php foreach ($items as $item): ?>
          <div class="inv-card">
            <div class="inv-photo">
              <?php if ($item['photo_path']): ?>
                <img src="<?= e(UPLOAD_URL . $item['photo_path']) ?>" alt="<?= e($item['name']) ?>">
              <?php else: ?>
                <?= yz_icon(category_icon_key($item['category'])) ?>
              <?php endif; ?>
            </div>
            <div class="inv-body">
              <div class="inv-cat"><?= e($item['category']) ?></div>
              <div class="inv-name"><?= e($item['name']) ?></div>
              <div class="inv-meta">
                <span class="inv-id mono"><?= e($item['claim_id']) ?></span>
                <span class="reg-status <?= status_class($item['status']) ?>"><?= status_label($item['status']) ?></span>
              </div>
              <?php if ($item['status'] === 'for_sale' && $item['price']): ?>
                <div class="inv-price">$<?= number_format((float)$item['price'], 2) ?></div>
              <?php endif; ?>
              <div class="inv-actions">
                <?php if ($item['status'] === 'owned'): ?>
                  <a href="claim-sell.php?id=<?= (int)$item['id'] ?>">List for sale</a>
                  <form class="inline-action" method="post" action="claim-status.php" data-confirm="Mark this item as lost? Its status will change publicly.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="lost">
                    <button type="submit">Report lost</button>
                  </form>
                <?php elseif ($item['status'] === 'for_sale'): ?>
                  <form class="inline-action" method="post" action="claim-status.php" data-confirm="Mark this item as sold? It will move out of your active listings." data-confirm-title="Mark as sold?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="sold">
                    <button type="submit">Mark sold</button>
                  </form>
                  <form class="inline-action" method="post" action="claim-status.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="cancel_sale">
                    <button type="submit">Cancel listing</button>
                  </form>
                <?php elseif ($item['status'] === 'lost'): ?>
                  <form class="inline-action" method="post" action="claim-status.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="found">
                    <button type="submit">Mark found</button>
                  </form>
                <?php endif; ?>
                <form class="inline-action" method="post" action="claim-status.php" data-confirm="This permanently removes the claim and its photo. This can't be undone." data-confirm-title="Delete this item?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="dash-danger">Delete</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>

<?php require __DIR__ . '/includes/chat-widget.php'; ?>
<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>