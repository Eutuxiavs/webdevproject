<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: offers.php');
    exit;
}
verify_csrf();

$user    = current_user();
$offerId = (int)($_POST['offer_id'] ?? 0);
$rating  = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($rating < 1 || $rating > 5) {
    flash_set('error', 'Invalid rating.');
    header('Location: offers.php');
    exit;
}
if (mb_strlen($comment) > 500) {
    $comment = mb_substr($comment, 0, 500);
}

$stmt = $pdo->prepare('SELECT * FROM offers WHERE id = ? AND status = "completed"');
$stmt->execute([$offerId]);
$offer = $stmt->fetch();

if (!$offer) {
    flash_set('error', 'That deal was not found or is not completed yet.');
    header('Location: offers.php');
    exit;
}

$isSeller = (int)$offer['seller_id'] === $user['id'];
$isBuyer  = (int)$offer['buyer_id'] === $user['id'];

if (!$isSeller && !$isBuyer) {
    flash_set('error', "You weren't part of this deal.");
    header('Location: offers.php');
    exit;
}

$revieweeId = $isSeller ? $offer['buyer_id'] : $offer['seller_id'];

try {
    $stmt = $pdo->prepare(
        'INSERT INTO reviews (offer_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$offer['id'], $user['id'], $revieweeId, $rating, $comment ?: null]);
    flash_set('success', 'Thanks for the review.');
} catch (PDOException $e) {
    // Unique constraint (offer_id, reviewer_id) — already reviewed this deal.
    flash_set('error', "You've already reviewed this deal.");
}

header('Location: offers.php');
exit;
