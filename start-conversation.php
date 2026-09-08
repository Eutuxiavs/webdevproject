<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$itemId = (int)($_GET['item'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM items WHERE id = ? AND status IN ("for_sale", "lost")');
$stmt->execute([$itemId]);
$item = $stmt->fetch();
if ($item) { track_recently_viewed((int)$item['id']); }

if (!$item) {
    flash_set('error', 'That listing is no longer available.');
    header('Location: browse.php');
    exit;
}

if ((int)$item['user_id'] === $user['id']) {
    flash_set('error', "You can't message yourself about your own listing.");
    header('Location: dashboard.php');
    exit;
}

if (users_blocked($pdo, $user['id'], (int)$item['user_id'])) {
    flash_set('error', "You can't contact this user.");
    header('Location: browse.php');
    exit;
}

// Find existing conversation or create one.
$stmt = $pdo->prepare('SELECT id FROM conversations WHERE item_id = ? AND buyer_id = ?');
$stmt->execute([$item['id'], $user['id']]);
$conv = $stmt->fetch();

if (!$conv) {
    $stmt = $pdo->prepare('INSERT INTO conversations (item_id, buyer_id, seller_id) VALUES (?, ?, ?)');
    $stmt->execute([$item['id'], $user['id'], $item['user_id']]);
    $convId = (int)$pdo->lastInsertId();
} else {
    $convId = (int)$conv['id'];
}

header('Location: messages.php?conversation=' . $convId);
exit;