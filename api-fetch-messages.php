<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in.']);
    exit;
}

$userId = current_user_id();
$convId = (int)($_GET['conversation_id'] ?? 0);
$afterId = (int)($_GET['after_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)');
$stmt->execute([$convId, $userId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not part of this conversation.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, body, sender_id, created_at FROM messages
     WHERE conversation_id = ? AND id > ? ORDER BY id ASC'
);
$stmt->execute([$convId, $afterId]);
$rows = $stmt->fetchAll();

// Mark any messages sent TO this user in this conversation as read.
$pdo->prepare(
    'UPDATE messages SET read_at = NOW()
     WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL'
)->execute([$convId, $userId]);

echo json_encode(['ok' => true, 'messages' => $rows]);
