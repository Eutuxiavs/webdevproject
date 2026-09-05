<?php
/**
 * chat-widget.php
 * Include this on any page AFTER require_login() has run.
 * Renders a floating circular button (bottom-right) that expands into
 * a conversation list + inline thread, using the same api-send-message.php
 * / api-fetch-messages.php endpoints as messages.php.
 */
$widgetUser = current_user();
$stmt = $pdo->prepare(
    'SELECT c.id, i.name AS item_name,
            CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END AS other_id,
            u.name AS other_name,
            (SELECT body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body,
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.read_at IS NULL) AS unread_count
     FROM conversations c
     JOIN items i ON i.id = c.item_id
     JOIN users u ON u.id = (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END)
     WHERE c.buyer_id = ? OR c.seller_id = ?
     ORDER BY c.id DESC'
);
$stmt->execute([$widgetUser['id'], $widgetUser['id'], $widgetUser['id'], $widgetUser['id'], $widgetUser['id']]);
$widgetConversations = $stmt->fetchAll();

$totalUnread = array_sum(array_column($widgetConversations, 'unread_count'));
$unreadLabel = $totalUnread > 99 ? '99+' : (string)$totalUnread;
?>
<div class="cw" id="chatWidget" data-self="<?= (int)$widgetUser['id'] ?>">
  <button class="cw-toggle" id="cwToggle" type="button" aria-label="Messages">
    <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12H7l-3 3V4z"/><circle cx="9" cy="10" r="1" fill="#ffffff" stroke="none"/><circle cx="12" cy="10" r="1" fill="#ffffff" stroke="none"/><circle cx="15" cy="10" r="1" fill="#ffffff" stroke="none"/></svg>
    <?php if ($totalUnread > 0): ?><span class="cw-badge"><?= e($unreadLabel) ?></span><?php endif; ?>
  </button>

  <div class="cw-panel" id="cwPanel">
    <div class="cw-list-view" id="cwListView">
      <div class="cw-head">
        <span>Messages</span>
        <a href="messages.php" class="cw-full">Full view</a>
      </div>
      <div class="cw-list">
        <?php if (!$widgetConversations): ?>
          <p class="cw-empty">No conversations yet.<br>Message a seller from the marketplace to start one.</p>
        <?php else: ?>
          <?php foreach ($widgetConversations as $c): ?>
            <button type="button" class="cw-convo"
                    data-conversation="<?= (int)$c['id'] ?>"
                    data-name="<?= e($c['other_name']) ?>"
                    data-item="<?= e($c['item_name']) ?>">
              <span class="cw-convo-name"><?= e($c['other_name']) ?><?php if ($c['unread_count'] > 0): ?><span class="cw-convo-unread"><?= (int)$c['unread_count'] ?></span><?php endif; ?></span>
              <span class="cw-convo-item"><?= e($c['item_name']) ?></span>
              <span class="cw-convo-snip"><?= e(mb_strimwidth($c['last_body'] ?? 'No messages yet.', 0, 40, '…')) ?></span>
            </button>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="cw-thread-view" id="cwThreadView" style="display:none;">
      <div class="cw-head">
        <button type="button" class="cw-back" id="cwBack">&larr;</button>
        <div class="cw-thread-title">
          <span id="cwThreadName"></span>
          <small id="cwThreadItem"></small>
        </div>
      </div>
      <div class="cw-messages" id="cwMessages"></div>
      <form class="cw-input-row" id="cwForm">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="conversation_id" id="cwConvId" value="">
        <input type="text" name="body" id="cwInput" placeholder="Write a message…" autocomplete="off" maxlength="2000" required>
        <button type="submit" aria-label="Send">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
        </button>
      </form>
    </div>
  </div>
</div>