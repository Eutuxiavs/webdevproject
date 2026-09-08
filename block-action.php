<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: browse.php');
    exit;
}
verify_csrf();

$user     = current_user();
$targetId = (int)($_POST['user_id'] ?? 0);
$action   = $_POST['action'] ?? '';

if ($targetId === $user['id'] || $targetId <= 0) {
    header('Location: browse.php');
    exit;
}

if ($action === 'block') {
    $stmt = $pdo->prepare('INSERT IGNORE INTO blocks (blocker_id, blocked_id) VALUES (?, ?)');
    $stmt->execute([$user['id'], $targetId]);
    flash_set('success', 'User blocked. They can no longer message you or make offers to you.');
} elseif ($action === 'unblock') {
    $stmt = $pdo->prepare('DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?');
    $stmt->execute([$user['id'], $targetId]);
    flash_set('success', 'User unblocked.');
}

header('Location: profile-view.php?user=' . $targetId);
exit;
