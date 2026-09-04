<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Security check failed.']);
    exit;
}

$userId = current_user_id();
$convId = (int)($_POST['conversation_id'] ?? 0);
$body   = trim($_POST['body'] ?? '');

if ($body === '' || mb_strlen($body) > 2000) {
    echo json_encode(['ok' => false, 'error' => 'Message is empty or too long.']);
    exit;
}

// Confirm this user is actually part of the conversation.
$stmt = $pdo->prepare('SELECT id FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)');
$stmt->execute([$convId, $userId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not part of this conversation.']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)');
$stmt->execute([$convId, $userId, $body]);

echo json_encode([
    'ok' => true,
    'message' => [
        'id'         => (int)$pdo->lastInsertId(),
        'body'       => $body,
        'sender_id'  => $userId,
        'created_at' => date('c'),
    ],
]);
