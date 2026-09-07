<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: offers.php');
    exit;
}
verify_csrf();

$user     = current_user();
$offerId  = (int)($_POST['offer_id'] ?? 0);
$action   = $_POST['action'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM offers WHERE id = ?');
$stmt->execute([$offerId]);
$offer = $stmt->fetch();

if (!$offer) {
    flash_set('error', 'That offer was not found.');
    header('Location: offers.php');
    exit;
}

$isSeller = (int)$offer['seller_id'] === $user['id'];
$isBuyer  = (int)$offer['buyer_id'] === $user['id'];

if (!$isSeller && !$isBuyer) {
    flash_set('error', "You're not part of this offer.");
    header('Location: offers.php');
    exit;
}

switch ($action) {

    case 'accept':
        if (!$isSeller || $offer['status'] !== 'pending') { break; }

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE offers SET status = "accepted" WHERE id = ?')->execute([$offer['id']]);
        // Reserve the item so no one else can offer on it while this deal is in progress.
        $pdo->prepare('UPDATE items SET status = "reserved" WHERE id = ? AND user_id = ?')
            ->execute([$offer['item_id'], $user['id']]);
        // Auto-decline every other pending offer on the same item.
        $pdo->prepare('UPDATE offers SET status = "declined" WHERE item_id = ? AND id != ? AND status = "pending"')
            ->execute([$offer['item_id'], $offer['id']]);
        $pdo->commit();

        flash_set('success', 'Offer accepted. The item is now reserved for this buyer.');
        break;

    case 'decline':
        if (!$isSeller || $offer['status'] !== 'pending') { break; }
        $pdo->prepare('UPDATE offers SET status = "declined" WHERE id = ?')->execute([$offer['id']]);
        flash_set('success', 'Offer declined.');
        break;

    case 'withdraw':
        if (!$isBuyer || $offer['status'] !== 'pending') { break; }
        $pdo->prepare('UPDATE offers SET status = "cancelled" WHERE id = ?')->execute([$offer['id']]);
        flash_set('success', 'Offer withdrawn.');
        break;

    case 'cancel':
        if (!$isSeller && !$isBuyer) { break; }
        if ($offer['status'] !== 'accepted') { break; }

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE offers SET status = "cancelled" WHERE id = ?')->execute([$offer['id']]);
        // Put the item back up for sale so the seller isn't stuck.
        $pdo->prepare('UPDATE items SET status = "for_sale" WHERE id = ? AND status = "reserved"')
            ->execute([$offer['item_id']]);
        $pdo->commit();

        flash_set('success', 'Deal cancelled. The item is back up for sale.');
        break;

    case 'confirm':
        if ($offer['status'] !== 'accepted') { break; }

        $column = $isSeller ? 'seller_confirmed' : 'buyer_confirmed';
        $pdo->prepare("UPDATE offers SET $column = 1 WHERE id = ?")->execute([$offer['id']]);

        // Re-fetch to see if BOTH sides have now confirmed.
        $stmt = $pdo->prepare('SELECT * FROM offers WHERE id = ?');
        $stmt->execute([$offer['id']]);
        $updated = $stmt->fetch();

        if ($updated['buyer_confirmed'] && $updated['seller_confirmed']) {
            // Both sides agree the handover happened — NOW ownership actually transfers.
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE offers SET status = "completed" WHERE id = ?')->execute([$offer['id']]);
            $pdo->prepare('UPDATE items SET user_id = ?, status = "owned", price = NULL WHERE id = ?')
                ->execute([$offer['buyer_id'], $offer['item_id']]);
            $pdo->commit();
            flash_set('success', 'Both sides confirmed — ownership has been transferred. The item now belongs to the buyer.');
        } else {
            flash_set('success', "Confirmed on your end. Waiting for the other side to confirm too before anything transfers.");
        }
        break;

    default:
        flash_set('error', 'Unknown action.');
}

header('Location: offers.php');
exit;
