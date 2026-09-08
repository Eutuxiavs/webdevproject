<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: offers.php');
    exit;
}
verify_csrf();
auto_expire_offers($pdo);

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

        // If this is a trade, make sure the buyer's offered item is still theirs and still available.
        if ($offer['trade_item_id']) {
            $stmt = $pdo->prepare('SELECT status, user_id FROM items WHERE id = ?');
            $stmt->execute([$offer['trade_item_id']]);
            $tradeItem = $stmt->fetch();
            if (!$tradeItem || $tradeItem['status'] !== 'owned' || (int)$tradeItem['user_id'] !== (int)$offer['buyer_id']) {
                flash_set('error', "The buyer's trade item is no longer available. This offer can't be accepted.");
                header('Location: offers.php');
                exit;
            }
        }

        $pdo->beginTransaction();
        $pdo->prepare('UPDATE offers SET status = "accepted" WHERE id = ?')->execute([$offer['id']]);
        $pdo->prepare('UPDATE items SET status = "reserved" WHERE id = ? AND user_id = ?')
            ->execute([$offer['item_id'], $user['id']]);
        if ($offer['trade_item_id']) {
            $pdo->prepare('UPDATE items SET status = "reserved" WHERE id = ?')->execute([$offer['trade_item_id']]);
        }
        // Auto-decline every other pending offer on the same item.
        $pdo->prepare('UPDATE offers SET status = "declined" WHERE item_id = ? AND id != ? AND status = "pending"')
            ->execute([$offer['item_id'], $offer['id']]);
        $pdo->commit();

        flash_set('success', 'Offer accepted. The item(s) are now reserved for this deal.');
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
        $pdo->prepare('UPDATE items SET status = "for_sale" WHERE id = ? AND status = "reserved"')
            ->execute([$offer['item_id']]);
        if ($offer['trade_item_id']) {
            $pdo->prepare('UPDATE items SET status = "owned" WHERE id = ? AND status = "reserved"')
                ->execute([$offer['trade_item_id']]);
        }
        $pdo->commit();

        flash_set('success', 'Deal cancelled. Item(s) are back to normal.');
        break;

    case 'confirm':
        if ($offer['status'] !== 'accepted') { break; }

        if ($isSeller) {
            $pdo->prepare('UPDATE offers SET seller_confirmed = 1 WHERE id = ?')->execute([$offer['id']]);
        } else {
            $pdo->prepare('UPDATE offers SET buyer_confirmed = 1 WHERE id = ?')->execute([$offer['id']]);
        }

        $stmt = $pdo->prepare('SELECT * FROM offers WHERE id = ?');
        $stmt->execute([$offer['id']]);
        $updated = $stmt->fetch();

        if ($updated['buyer_confirmed'] && $updated['seller_confirmed']) {
            // Both sides agree the handover happened — transfer ownership now.
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE offers SET status = "completed" WHERE id = ?')->execute([$offer['id']]);

            // The seller's item goes to the buyer.
            $pdo->prepare('UPDATE items SET user_id = ?, status = "owned", price = NULL WHERE id = ?')
                ->execute([$offer['buyer_id'], $offer['item_id']]);
            $pdo->prepare(
                'INSERT INTO ownership_history (item_id, previous_owner_id, new_owner_id, offer_id) VALUES (?, ?, ?, ?)'
            )->execute([$offer['item_id'], $offer['seller_id'], $offer['buyer_id'], $offer['id']]);

            // If it was a trade, the buyer's offered item goes to the seller too.
            if ($offer['trade_item_id']) {
                $pdo->prepare('UPDATE items SET user_id = ?, status = "owned", price = NULL WHERE id = ?')
                    ->execute([$offer['seller_id'], $offer['trade_item_id']]);
                $pdo->prepare(
                    'INSERT INTO ownership_history (item_id, previous_owner_id, new_owner_id, offer_id) VALUES (?, ?, ?, ?)'
                )->execute([$offer['trade_item_id'], $offer['buyer_id'], $offer['seller_id'], $offer['id']]);
            }

            // Platform takes a simulated cut of any cash portion of the deal.
            if ((float)$offer['offer_price'] > 0) {
                $fee = round((float)$offer['offer_price'] * PLATFORM_FEE_PERCENT / 100, 2);
                $pdo->prepare(
                    'INSERT INTO platform_revenue (source, amount, user_id, item_id, offer_id) VALUES ("transaction_fee", ?, ?, ?, ?)'
                )->execute([$fee, $offer['seller_id'], $offer['item_id'], $offer['id']]);
            }

            $pdo->commit();
            flash_set('success', 'Both sides confirmed — ownership has been transferred.');
        } else {
            flash_set('success', 'Confirmed on your end. Waiting for the other side before anything transfers.');
        }
        break;

    case 'dispute':
        if (!$isSeller && !$isBuyer) { break; }
        if ($offer['status'] !== 'completed') { break; }
        $pdo->prepare('UPDATE offers SET status = "disputed" WHERE id = ?')->execute([$offer['id']]);
        flash_set('success', "Marked as disputed. Note: there's no automated resolution for this — it's a flag so both sides (and you, if you review this project) can see something went wrong.");
        break;

    case 'set_meetup':
        if (!$isSeller && !$isBuyer) { break; }
        if ($offer['status'] !== 'accepted') { break; }
        $note = clean_text($_POST['meetup_note'] ?? '');
        if (mb_strlen($note) > 300) { $note = mb_substr($note, 0, 300); }
        $pdo->prepare('UPDATE offers SET meetup_note = ? WHERE id = ?')->execute([$note ?: null, $offer['id']]);
        flash_set('success', 'Meetup note updated.');
        break;

    default:
        flash_set('error', 'Unknown action.');
}

header('Location: offers.php');
exit;