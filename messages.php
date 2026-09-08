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
$pageTitle = 'Messages — YONZON CLAIM';
$activeNav = '';
require __DIR__ . '/includes/header-dash.php';
?>

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

<?php require __DIR__ . '/includes/footer-dash.php'; ?>