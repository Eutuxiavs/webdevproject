<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

$stmt = $pdo->prepare(
    'SELECT c.id, c.item_id, i.name AS item_name, i.photo_path, i.claim_id,
            CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END AS other_id,
            u.name AS other_name,
            (SELECT body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body
     FROM conversations c
     JOIN items i ON i.id = c.item_id
     JOIN users u ON u.id = (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END)
     WHERE c.buyer_id = ? OR c.seller_id = ?
     ORDER BY c.id DESC'
);
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$conversations = $stmt->fetchAll();

$activeId = (int)($_GET['conversation'] ?? 0);
$active = null;
$messages = [];

if ($activeId) {
    foreach ($conversations as $c) {
        if ((int)$c['id'] === $activeId) { $active = $c; break; }
    }
    if ($active) {
        $stmt = $pdo->prepare(
            'SELECT m.id, m.body, m.created_at, m.sender_id, u.name AS sender_name
             FROM messages m JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ? ORDER BY m.id ASC'
        );
        $stmt->execute([$activeId]);
        $messages = $stmt->fetchAll();

        // Mark any messages sent TO this user in this conversation as read.
        $pdo->prepare(
            'UPDATE messages SET read_at = NOW()
             WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL'
        )->execute([$activeId, $user['id']]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — YONZON CLAIM</title>
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
      <div class="dash-summary">Buyer &amp; seller correspondence</div>
      <h1 class="dash-title">Messages.</h1>
    </div>
  </div>

  <div class="chat-shell">
    <aside class="chat-list">
      <?php if (!$conversations): ?>
        <p class="dash-side-empty">No conversations yet. Message a seller from the marketplace to start one.</p>
      <?php else: ?>
        <?php foreach ($conversations as $c): ?>
          <a class="chat-list-item <?= ((int)$c['id'] === $activeId) ? 'active' : '' ?>" href="messages.php?conversation=<?= (int)$c['id'] ?>">
            <div class="chat-list-top">
              <span class="chat-list-name"><?= e($c['other_name']) ?></span>
            </div>
            <div class="chat-list-item-title"><?= e($c['item_name']) ?></div>
            <div class="chat-list-snippet"><?= e(mb_strimwidth($c['last_body'] ?? 'No messages yet.', 0, 46, '…')) ?></div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </aside>

    <section class="chat-thread">
      <?php if (!$active): ?>
        <div class="chat-empty">Select a conversation to view it.</div>
      <?php else: ?>
        <div class="chat-thread-head">
          <div>
            <div class="chat-thread-name"><?= e($active['other_name']) ?></div>
            <div class="chat-thread-item">Re: <?= e($active['item_name']) ?> &middot; <span class="mono"><?= e($active['claim_id']) ?></span></div>
          </div>
        </div>

        <div class="chat-messages" id="chatMessages" data-conversation="<?= (int)$active['id'] ?>" data-self="<?= (int)$user['id'] ?>">
          <?php foreach ($messages as $m): ?>
            <div class="chat-bubble-row <?= ((int)$m['sender_id'] === $user['id']) ? 'mine' : '' ?>" data-id="<?= (int)$m['id'] ?>">
              <div class="chat-bubble"><?= nl2br(e($m['body'])) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <form class="chat-input-row" id="chatForm">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="conversation_id" value="<?= (int)$active['id'] ?>">
          <input type="text" name="body" id="chatInput" placeholder="Write a message…" autocomplete="off" maxlength="2000" required>
          <button type="submit" class="btn btn-primary">Send</button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</main>

<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>