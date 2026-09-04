<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_login();

$user = current_user();

$stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();

$counts = ['owned' => 0, 'for_sale' => 0, 'lost' => 0, 'warranty' => 0, 'sold' => 0];
foreach ($items as $it) { $counts[$it['status']] = ($counts[$it['status']] ?? 0) + 1; }

// Conversations involving this user, most recent first, with a bit of context.
$stmt = $pdo->prepare(
    'SELECT c.id, c.item_id, i.name AS item_name,
            CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END AS other_id,
            u.name AS other_name,
            (SELECT body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
            (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_at
     FROM conversations c
     JOIN items i ON i.id = c.item_id
     JOIN users u ON u.id = (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END)
     WHERE c.buyer_id = ? OR c.seller_id = ?
     ORDER BY last_at DESC
     LIMIT 5'
);
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$conversations = $stmt->fetchAll();

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
<link rel="stylesheet" href="style.css">
</head>
<body class="dash-body">

<div class="letterhead-top dash-letterhead">
  <div class="dash-wrap">
    <div class="lh-main" style="padding:18px 0;">
      <a class="mark" href="dashboard.php">
        <svg viewBox="0 0 40 40" fill="none"><path d="M20 3L36 20L20 37L4 20L20 3Z" stroke="currentColor" stroke-width="1"/><path d="M12 12L28 28M28 12L12 28" stroke="currentColor" stroke-width="1"/><path d="M20 3V37" stroke="currentColor" stroke-width="1"/></svg>
        <div class="mark-word"><div class="a">YONZON</div><div class="b">Claim Registry</div></div>
      </a>
      <nav class="dash-nav">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="messages.php">Messages<?php if ($conversations): ?><span class="dash-dot"></span><?php endif; ?></a>
        <a href="index.php#market">Marketplace</a>
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

  <div class="dash-grid">
    <section class="dash-claims">
      <?php if (!$items): ?>
        <div class="dash-empty">
          <p>Nothing filed yet.</p>
          <a class="btn btn-ghost" href="claim-add.php">Register your first item</a>
        </div>
      <?php else: ?>
        <div class="dir-list dash-list">
          <?php foreach ($items as $item): ?>
            <div class="dir-row dash-item-row">
              <div class="rn mono"><?= e(strtoupper(substr($item['category'],0,2))) ?></div>
              <div class="name-wrap">
                <span class="name"><?= e($item['name']) ?></span>
                <span class="leader"></span>
              </div>
              <div class="stat">
                <span class="reg-status <?= status_class($item['status']) ?>"><?= status_label($item['status']) ?></span>
              </div>
              <div class="desc">
                <span class="mono"><?= e($item['claim_id']) ?></span>
                <?php if ($item['status'] === 'for_sale' && $item['price']): ?>
                  &middot; <span style="color:var(--accent-l); font-weight:600;">$<?= number_format((float)$item['price'], 2) ?></span>
                <?php endif; ?>
                &middot; <span class="dash-links">
                  <?php if ($item['status'] === 'owned'): ?>
                    <a href="claim-sell.php?id=<?= (int)$item['id'] ?>">List for sale</a>
                    <form class="inline-action" method="post" action="claim-status.php" onsubmit="return confirm('Mark this item as lost?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                      <input type="hidden" name="action" value="lost">
                      <button type="submit">Report lost</button>
                    </form>
                  <?php elseif ($item['status'] === 'for_sale'): ?>
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
                  <form class="inline-action" method="post" action="claim-status.php" onsubmit="return confirm('Remove this claim permanently?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="dash-danger">Delete</button>
                  </form>
                </span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <aside class="dash-side">
      <div class="dash-side-head">
        <span>Messages</span>
        <a href="messages.php">View all</a>
      </div>
      <?php if (!$conversations): ?>
        <p class="dash-side-empty">No conversations yet.</p>
      <?php else: ?>
        <?php foreach ($conversations as $c): ?>
          <a class="dash-convo" href="messages.php?conversation=<?= (int)$c['id'] ?>">
            <div class="dash-convo-top">
              <span class="dash-convo-name"><?= e($c['other_name']) ?></span>
              <span class="dash-convo-item"><?= e($c['item_name']) ?></span>
            </div>
            <div class="dash-convo-msg"><?= e(mb_strimwidth($c['last_body'] ?? 'No messages yet.', 0, 64, '…')) ?></div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </aside>
  </div>

</main>

<script src="main.js"></script>
</body>
</html>
