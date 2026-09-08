<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
auto_expire_offers($pdo);

$stmt = $pdo->prepare(
    'SELECT o.*, i.name AS item_name, i.claim_id, i.photo_path, i.category,
            u.name AS buyer_name,
            ti.name AS trade_item_name, ti.photo_path AS trade_item_photo, ti.category AS trade_item_category
     FROM offers o
     JOIN items i ON i.id = o.item_id
     JOIN users u ON u.id = o.buyer_id
     LEFT JOIN items ti ON ti.id = o.trade_item_id
     WHERE o.seller_id = ?
     ORDER BY o.status = "pending" DESC, o.updated_at DESC'
);
$stmt->execute([$user['id']]);
$incoming = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT o.*, i.name AS item_name, i.claim_id, i.photo_path, i.category,
            u.name AS seller_name,
            ti.name AS trade_item_name, ti.photo_path AS trade_item_photo, ti.category AS trade_item_category
     FROM offers o
     JOIN items i ON i.id = o.item_id
     JOIN users u ON u.id = o.seller_id
     LEFT JOIN items ti ON ti.id = o.trade_item_id
     WHERE o.buyer_id = ?
     ORDER BY o.status = "pending" DESC, o.updated_at DESC'
);
$stmt->execute([$user['id']]);
$outgoing = $stmt->fetchAll();

// Which completed offers has this user already reviewed?
$stmt = $pdo->prepare('SELECT offer_id FROM reviews WHERE reviewer_id = ?');
$stmt->execute([$user['id']]);
$reviewedOfferIds = array_column($stmt->fetchAll(), 'offer_id');

$success = flash_get('success');
$error   = flash_get('error');

function offer_status_pill(string $status): string {
    $map = [
        'pending'   => ['Pending', ''],
        'accepted'  => ['Accepted', 'sale'],
        'declined'  => ['Declined', 'lost'],
        'cancelled' => ['Cancelled', 'lost'],
        'completed' => ['Completed', 'sold'],
        'expired'   => ['Expired', 'lost'],
        'disputed'  => ['Disputed', 'lost'],
    ];
    [$label, $class] = $map[$status] ?? [ucfirst($status), ''];
    return '<span class="reg-status ' . $class . '">' . e($label) . '</span>';
}

function offer_terms_line(array $o): string {
    $parts = [];
    if ((float)$o['offer_price'] > 0) $parts[] = '$' . number_format((float)$o['offer_price'], 2);
    if ($o['trade_item_name']) $parts[] = 'trade: ' . htmlspecialchars($o['trade_item_name']);
    return $parts ? implode(' + ', $parts) : 'Cash offer';
}

function render_offer_row(array $o, string $perspective, PDO $pdo, int $userId, array $reviewedOfferIds): void {
    $otherName = $perspective === 'incoming' ? $o['buyer_name'] : $o['seller_name'];
    $otherLabel = $perspective === 'incoming' ? 'From' : 'Sold by';
    $myConfirmedKey = $perspective === 'incoming' ? 'seller_confirmed' : 'buyer_confirmed';
    ?>
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
            <div class="dash-summary" style="margin:4px 0 0;"><?= e($otherLabel) ?> <?= e($otherName) ?> &middot; <span class="mono"><?= e($o['claim_id']) ?></span></div>
          </div>
          <div style="text-align:right;">
            <div class="inv-price"><?= offer_terms_line($o) ?></div>
            <?= offer_status_pill($o['status']) ?>
          </div>
        </div>

        <?php if ($o['trade_item_id']): ?>
          <div class="offer-trade-item">
            <div class="offer-trade-photo">
              <?php if ($o['trade_item_photo']): ?>
                <img src="<?= e(UPLOAD_URL . $o['trade_item_photo']) ?>" alt="">
              <?php else: ?>
                <?= yz_icon(category_icon_key($o['trade_item_category'])) ?>
              <?php endif; ?>
            </div>
            <span>Offered in trade: <strong><?= e($o['trade_item_name']) ?></strong></span>
          </div>
        <?php endif; ?>

        <?php if ($o['message']): ?><div class="offer-msg">&ldquo;<?= e($o['message']) ?>&rdquo;</div><?php endif; ?>

        <?php if ($o['status'] === 'accepted'): ?>
          <div class="meetup-box">
            <div class="meetup-label">Meetup / handover plan</div>
            <?php if ($o['meetup_note']): ?>
              <div class="meetup-current"><?= nl2br(e($o['meetup_note'])) ?></div>
            <?php else: ?>
              <div class="meetup-current empty">Nothing set yet — agree on a time and place.</div>
            <?php endif; ?>
            <form method="post" action="offer-action.php" class="meetup-form">
              <?= csrf_field() ?>
              <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
              <input type="hidden" name="action" value="set_meetup">
              <input type="text" name="meetup_note" maxlength="300" placeholder="e.g. Sat 2pm, SM foodcourt" value="<?= e($o['meetup_note'] ?? '') ?>">
              <button type="submit">Update</button>
            </form>
          </div>
        <?php endif; ?>

        <div class="inv-actions" style="margin-top:14px;">
          <?php if ($o['status'] === 'pending' && $perspective === 'incoming'): ?>
            <form class="inline-action" method="post" action="offer-action.php" data-confirm="Accept this offer? Other pending offers on this item will be automatically declined.">
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
          <?php elseif ($o['status'] === 'pending' && $perspective === 'outgoing'): ?>
            <form class="inline-action" method="post" action="offer-action.php" data-confirm="Withdraw this offer?">
              <?= csrf_field() ?>
              <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
              <input type="hidden" name="action" value="withdraw">
              <button type="submit" class="dash-danger">Withdraw</button>
            </form>
          <?php elseif ($o['status'] === 'accepted'): ?>
            <?php if ($o[$myConfirmedKey]): ?>
              <span class="offer-waiting">You confirmed. Waiting on the other side.</span>
            <?php else: ?>
              <form class="inline-action" method="post" action="offer-action.php" data-confirm="Only confirm once the handover actually happened." data-confirm-title="Confirm handover complete?">
                <?= csrf_field() ?>
                <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
                <input type="hidden" name="action" value="confirm">
                <button type="submit">Confirm handover complete</button>
              </form>
            <?php endif; ?>
            <form class="inline-action" method="post" action="offer-action.php" data-confirm="Cancel this deal? Item(s) return to normal." data-confirm-title="Cancel this deal?">
              <?= csrf_field() ?>
              <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
              <input type="hidden" name="action" value="cancel">
              <button type="submit" class="dash-danger">Cancel deal</button>
            </form>
          <?php elseif ($o['status'] === 'completed'): ?>
            <?php if (in_array((int)$o['id'], $reviewedOfferIds, true)): ?>
              <span class="offer-waiting">You've reviewed this deal. Thanks!</span>
            <?php else: ?>
              <button type="button" class="review-open-btn" data-offer="<?= (int)$o['id'] ?>" data-name="<?= e($otherName) ?>">Leave a review for <?= e($otherName) ?></button>
            <?php endif; ?>
            <form class="inline-action" method="post" action="offer-action.php" data-confirm="Only do this if something actually went wrong with this deal. There's no automated resolution — this just flags it." data-confirm-title="Dispute this deal?">
              <?= csrf_field() ?>
              <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
              <input type="hidden" name="action" value="dispute">
              <button type="submit" class="dispute-link">Something went wrong</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
}
$pageTitle = 'Offers — YONZON CLAIM';
$activeNav = 'offers';
require __DIR__ . '/includes/header-dash.php';
?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">Buying, selling, and trading — safely</div>
      <h1 class="dash-title">Offers.</h1>
    </div>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div class="safety-note">
    <strong>How this stays safe:</strong> accepting an offer doesn't transfer anything by itself.
    Ownership only moves once <strong>both</strong> sides separately click "Confirm handover complete" —
    meaning after the exchange actually happened. Neither side can force a transfer alone, and either
    side can cancel before that point. Meet in a safe public place for in-person handoffs, and never
    send payment before agreeing on the method with the other person through the chat.
  </div>

  <section style="margin-top:44px;">
    <div class="kicker">As the seller</div>
    <h2 class="sec-title" style="font-size:22px; margin-bottom:20px;">Offers you've received</h2>

    <?php if (!$incoming): ?>
      <div class="dash-empty"><p>No one has made an offer on your items yet.</p></div>
    <?php else: ?>
      <div class="offer-list">
        <?php foreach ($incoming as $o): render_offer_row($o, 'incoming', $pdo, $user['id'], $reviewedOfferIds); endforeach; ?>
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
        <?php foreach ($outgoing as $o): render_offer_row($o, 'outgoing', $pdo, $user['id'], $reviewedOfferIds); endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Review modal (hidden until a "Leave a review" button is clicked) -->
  <div class="modal-overlay" id="reviewOverlay">
    <div class="modal-card">
      <div class="modal-title">Rate this deal</div>
      <form method="post" action="review-submit.php">
        <?= csrf_field() ?>
        <input type="hidden" name="offer_id" id="reviewOfferId" value="">
        <div class="star-picker" id="starPicker">
          <button type="button" data-star="1">&#9733;</button>
          <button type="button" data-star="2">&#9733;</button>
          <button type="button" data-star="3">&#9733;</button>
          <button type="button" data-star="4">&#9733;</button>
          <button type="button" data-star="5">&#9733;</button>
        </div>
        <input type="hidden" name="rating" id="ratingInput" value="5">
        <textarea name="comment" rows="3" maxlength="500" placeholder="Optional comment about the deal…" style="width:100%; margin:16px 0; background:var(--ink-3); border:1px solid var(--line); border-radius:4px; padding:10px 12px; color:var(--paper); font-family:'Inter'; font-size:13px;"></textarea>
        <div class="modal-actions">
          <button type="button" class="modal-btn" id="reviewCancel">Cancel</button>
          <button type="submit" class="modal-btn danger" style="background:var(--accent); border-color:var(--accent);">Submit review</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var overlay = document.getElementById('reviewOverlay');
    var offerIdInput = document.getElementById('reviewOfferId');
    var ratingInput = document.getElementById('ratingInput');
    var stars = document.querySelectorAll('#starPicker button');

    function setStars(n) {
      stars.forEach(function (s) {
        s.classList.toggle('filled', parseInt(s.dataset.star, 10) <= n);
      });
      ratingInput.value = n;
    }
    setStars(5);
    stars.forEach(function (s) {
      s.addEventListener('click', function () { setStars(parseInt(s.dataset.star, 10)); });
    });

    document.querySelectorAll('.review-open-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        offerIdInput.value = btn.dataset.offer;
        setStars(5);
        overlay.classList.add('open');
      });
    });
    document.getElementById('reviewCancel').addEventListener('click', function () {
      overlay.classList.remove('open');
    });
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });
  </script>
</main>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>