<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

$stmt = $pdo->prepare(
    'SELECT o.*, i.name AS item_name, i.claim_id, i.photo_path, i.category, u.name AS buyer_name
     FROM offers o
     JOIN items i ON i.id = o.item_id
     JOIN users u ON u.id = o.buyer_id
     WHERE o.seller_id = ?
     ORDER BY o.status = "pending" DESC, o.updated_at DESC'
);
$stmt->execute([$user['id']]);
$incoming = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT o.*, i.name AS item_name, i.claim_id, i.photo_path, i.category, u.name AS seller_name
     FROM offers o
     JOIN items i ON i.id = o.item_id
     JOIN users u ON u.id = o.seller_id
     WHERE o.buyer_id = ?
     ORDER BY o.status = "pending" DESC, o.updated_at DESC'
);
$stmt->execute([$user['id']]);
$outgoing = $stmt->fetchAll();

$success = flash_get('success');
$error   = flash_get('error');

function offer_status_pill(string $status): string {
    $map = [
        'pending'   => ['Pending', ''],
        'accepted'  => ['Accepted', 'sale'],
        'declined'  => ['Declined', 'lost'],
        'cancelled' => ['Cancelled', 'lost'],
        'completed' => ['Completed', 'sold'],
    ];
    [$label, $class] = $map[$status] ?? [ucfirst($status), ''];
    return '<span class="reg-status ' . $class . '">' . e($label) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Offers — YONZON CLAIM</title>
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
        <a href="offers.php" class="active">Offers</a>
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
      <div class="dash-summary">Buying and selling, safely</div>
      <h1 class="dash-title">Offers.</h1>
    </div>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div class="safety-note">
    <strong>How this stays safe:</strong> accepting an offer doesn't transfer anything by itself.
    Ownership only moves to the buyer once <strong>both</strong> the buyer and seller separately
    click "Confirm handover complete" below — meaning after the payment and item exchange actually
    happened. Neither side can force a transfer alone, and either side can cancel before that point.
    Meet in a safe public place for in-person handoffs, and never send payment before agreeing on
    the method with the other person through the chat.
  </div>

  <section style="margin-top:44px;">
    <div class="kicker">As the seller</div>
    <h2 class="sec-title" style="font-size:22px; margin-bottom:20px;">Offers you've received</h2>

    <?php if (!$incoming): ?>
      <div class="dash-empty"><p>No one has made an offer on your items yet.</p></div>
    <?php else: ?>
      <div class="offer-list">
        <?php foreach ($incoming as $o): ?>
          <div class="offer-row">
            <div class="offer-photo">
              <?php if ($o['photo_path']): ?>
                <img src="<?= e(UPLOAD_URL . $o['photo_path']) ?>" alt="">
              <?php else: ?>
                <?= yz_icon(category_icon_key($o['category'])) ?>
              <?php endif; ?>
            </div>
            <div class="offer-body">
              <div class="offer-top">
                <div>
                  <div class="reg-name" style="font-size:16px;"><?= e($o['item_name']) ?></div>
                  <div class="dash-summary" style="margin:4px 0 0;">From <?= e($o['buyer_name']) ?> &middot; <span class="mono"><?= e($o['claim_id']) ?></span></div>
                </div>
                <div style="text-align:right;">
                  <div class="inv-price">$<?= number_format((float)$o['offer_price'], 2) ?></div>
                  <?= offer_status_pill($o['status']) ?>
                </div>
              </div>
              <?php if ($o['message']): ?><div class="offer-msg">&ldquo;<?= e($o['message']) ?>&rdquo;</div><?php endif; ?>

              <div class="inv-actions" style="margin-top:14px;">
                <?php if ($o['status'] === 'pending'): ?>
                  <form class="inline-action" method="post" action="offer-action.php" data-confirm="Accept this offer for $<?= number_format((float)$o['offer_price'], 2) ?>? Other pending offers on this item will be automatically declined.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit">Accept</button>
                  </form>
                  <form class="inline-action" method="post" action="offer-action.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                    <input type="hidden" name="action" value="decline">
                    <button type="submit" class="dash-danger">Decline</button>
                  </form>
                <?php elseif ($o['status'] === 'accepted'): ?>
                  <?php if ($o['seller_confirmed']): ?>
                    <span class="offer-waiting">You confirmed. Waiting on buyer.</span>
                  <?php else: ?>
                    <form class="inline-action" method="post" action="offer-action.php" data-confirm="Only confirm once you've actually handed over the item and received payment." data-confirm-title="Confirm handover complete?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                      <input type="hidden" name="action" value="confirm">
                      <button type="submit">Confirm handover complete</button>
                    </form>
                  <?php endif; ?>
                  <form class="inline-action" method="post" action="offer-action.php" data-confirm="Cancel this deal? The item goes back to For Sale." data-confirm-title="Cancel this deal?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="dash-danger">Cancel deal</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section style="margin-top:56px;">
    <div class="kicker">As the buyer</div>
    <h2 class="sec-title" style="font-size:22px; margin-bottom:20px;">Offers you've made</h2>

    <?php if (!$outgoing): ?>
      <div class="dash-empty"><p>You haven't made any offers yet. <a class="btn btn-ghost" href="browse.php" style="margin-top:14px; display:inline-block;">Browse the marketplace</a></p></div>
    <?php else: ?>
      <div class="offer-list">
        <?php foreach ($outgoing as $o): ?>
          <div class="offer-row">
            <div class="offer-photo">
              <?php if ($o['photo_path']): ?>
                <img src="<?= e(UPLOAD_URL . $o['photo_path']) ?>" alt="">
              <?php else: ?>
                <?= yz_icon(category_icon_key($o['category'])) ?>
              <?php endif; ?>
            </div>
            <div class="offer-body">
              <div class="offer-top">
                <div>
                  <div class="reg-name" style="font-size:16px;"><?= e($o['item_name']) ?></div>
                  <div class="dash-summary" style="margin:4px 0 0;">Sold by <?= e($o['seller_name']) ?> &middot; <span class="mono"><?= e($o['claim_id']) ?></span></div>
                </div>
                <div style="text-align:right;">
                  <div class="inv-price">$<?= number_format((float)$o['offer_price'], 2) ?></div>
                  <?= offer_status_pill($o['status']) ?>
                </div>
              </div>

              <div class="inv-actions" style="margin-top:14px;">
                <?php if ($o['status'] === 'pending'): ?>
                  <form class="inline-action" method="post" action="offer-action.php" data-confirm="Withdraw this offer?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                    <input type="hidden" name="action" value="withdraw">
                    <button type="submit" class="dash-danger">Withdraw</button>
                  </form>
                <?php elseif ($o['status'] === 'accepted'): ?>
                  <?php if ($o['buyer_confirmed']): ?>
                    <span class="offer-waiting">You confirmed. Waiting on seller.</span>
                  <?php else: ?>
                    <form class="inline-action" method="post" action="offer-action.php" data-confirm="Only confirm once you've actually received the item and sent payment." data-confirm-title="Confirm handover complete?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                      <input type="hidden" name="action" value="confirm">
                      <button type="submit">Confirm handover complete</button>
                    </form>
                  <?php endif; ?>
                  <form class="inline-action" method="post" action="offer-action.php" data-confirm="Cancel this deal?" data-confirm-title="Cancel this deal?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="dash-danger">Cancel deal</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . '/includes/chat-widget.php'; ?>
<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>
