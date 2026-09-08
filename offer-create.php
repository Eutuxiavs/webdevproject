<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$itemId = (int)($_GET['item'] ?? $_POST['item'] ?? 0);

$stmt = $pdo->prepare('SELECT i.*, u.name AS owner_name FROM items i JOIN users u ON u.id = i.user_id WHERE i.id = ? AND i.status = "for_sale"');
$stmt->execute([$itemId]);
$item = $stmt->fetch();
if ($item) { track_recently_viewed((int)$item['id']); }

if (!$item) {
    flash_set('error', 'That listing is no longer available for offers.');
    header('Location: browse.php');
    exit;
}
if ((int)$item['user_id'] === $user['id']) {
    flash_set('error', "You can't make an offer on your own item.");
    header('Location: browse.php');
    exit;
}
if (users_blocked($pdo, $user['id'], (int)$item['user_id'])) {
    flash_set('error', "You can't make an offer to this user.");
    header('Location: browse.php');
    exit;
}

// One pending offer per buyer per item — reuse it instead of duplicating.
$stmt = $pdo->prepare('SELECT id FROM offers WHERE item_id = ? AND buyer_id = ? AND status = "pending"');
$stmt->execute([$item['id'], $user['id']]);
if ($stmt->fetch()) {
    flash_set('success', 'You already have a pending offer on this item.');
    header('Location: offers.php');
    exit;
}

// The buyer's own items eligible to offer up in a trade (must be owned, and not this same item).
$stmt = $pdo->prepare('SELECT id, name, category, photo_path, claim_id FROM items WHERE user_id = ? AND status = "owned" ORDER BY name');
$stmt->execute([$user['id']]);
$tradeables = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $offerPrice  = trim($_POST['offer_price'] ?? '0');
    $offerPrice  = $offerPrice === '' ? 0 : $offerPrice;
    $tradeItemId = (int)($_POST['trade_item_id'] ?? 0);
    $message     = trim($_POST['message'] ?? '');

    if (!is_numeric($offerPrice) || (float)$offerPrice < 0) {
        $errors[] = 'Enter a valid cash amount (or leave it at 0 for a pure trade).';
    }
    if (mb_strlen($message) > 500) {
        $errors[] = 'Message is too long (max 500 characters).';
    }

    $tradeItem = null;
    if ($tradeItemId > 0) {
        foreach ($tradeables as $t) {
            if ((int)$t['id'] === $tradeItemId) { $tradeItem = $t; break; }
        }
        if (!$tradeItem) {
            $errors[] = 'Choose one of your own items to trade, or leave it as cash only.';
        }
    }

    if ((float)$offerPrice <= 0 && !$tradeItem) {
        $errors[] = "Offer either a cash amount, an item to trade, or both — can't send an empty offer.";
    }

    if (!$errors) {
        $offerType = $tradeItem
            ? ((float)$offerPrice > 0 ? 'trade_cash' : 'trade')
            : 'cash';

        $stmt = $pdo->prepare(
            'INSERT INTO offers (item_id, buyer_id, seller_id, offer_type, offer_price, trade_item_id, message, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))'
        );
        $stmt->execute([
            $item['id'], $user['id'], $item['user_id'], $offerType,
            (float)$offerPrice, $tradeItem ? $tradeItem['id'] : null, $message ?: null,
        ]);

        flash_set('success', "Offer sent to {$item['owner_name']}. It expires in 7 days if they don't respond.");
        header('Location: offers.php');
        exit;
    }
}
$pageTitle = 'Make an offer — YONZON CLAIM';
$activeNav = '';
require __DIR__ . '/includes/header-dash.php';
?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">Listed by <?= e($item['owner_name']) ?></div>
      <h1 class="dash-title">Offer on &ldquo;<?= e($item['name']) ?>&rdquo;</h1>
    </div>
    <a class="btn btn-ghost" href="browse.php">&larr; Back to marketplace</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <div class="offer-item-preview">
    <div class="offer-item-photo">
      <?php if ($item['photo_path']): ?>
        <img src="<?= e(UPLOAD_URL . $item['photo_path']) ?>" alt="<?= e($item['name']) ?>">
      <?php else: ?>
        <?= yz_icon(category_icon_key($item['category'])) ?>
      <?php endif; ?>
    </div>
    <div>
      <div class="reg-cat"><?= e($item['category']) ?></div>
      <div class="reg-name" style="font-size:20px;"><?= e($item['name']) ?></div>
      <div class="dash-summary" style="margin-top:8px;">Asking price: <strong style="color:var(--accent-l);">$<?= number_format((float)$item['price'], 2) ?></strong></div>
    </div>
  </div>

  <form method="post" class="form-card js-validate" novalidate>
    <?= csrf_field() ?>

    <div class="offer-type-tabs">
      <button type="button" class="offer-type-tab active" data-target="cash">Cash</button>
      <button type="button" class="offer-type-tab" data-target="trade">Trade an item</button>
      <button type="button" class="offer-type-tab" data-target="both">Item + cash</button>
    </div>

    <div id="offerFieldCash" class="offer-type-panel">
      <label class="field">
        <span>Your offer (USD)</span>
        <input type="number" name="offer_price" id="cashInput" step="0.01" min="0" value="<?= e($_POST['offer_price'] ?? number_format((float)$item['price'], 2, '.', '')) ?>">
      </label>
    </div>

    <div id="offerFieldTrade" class="offer-type-panel" style="display:none;">
      <?php if (!$tradeables): ?>
        <p class="dash-summary" style="margin-bottom:16px;">You don't have any owned items to trade yet. <a href="claim-add.php" style="color:var(--accent-l);">Register one first</a>, or offer cash instead.</p>
      <?php else: ?>
        <label class="field">
          <span>Item you're offering in trade</span>
          <select name="trade_item_id" id="tradeSelect">
            <option value="0">— Choose an item —</option>
            <?php foreach ($tradeables as $t): ?>
              <option value="<?= (int)$t['id'] ?>" <?= (int)($_POST['trade_item_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
                <?= e($t['name']) ?> (<?= e($t['claim_id']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      <?php endif; ?>
    </div>

    <label class="field">
      <span>Message to seller <em>(optional)</em></span>
      <textarea name="message" rows="3" maxlength="500" placeholder="e.g. Can you meet at..."><?= e($_POST['message'] ?? '') ?></textarea>
    </label>

    <div class="safety-note">
      <strong>How this stays safe:</strong> the seller must accept your offer before anything happens.
      After they accept, ownership only transfers once <em>both of you</em> separately confirm the
      handover went through — neither side can force it alone. This offer expires automatically
      in 7 days if the seller doesn't respond.
    </div>

    <button type="submit" class="btn btn-primary form-submit">Send offer</button>
  </form>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var tabs = document.querySelectorAll('.offer-type-tab');
    var cashPanel = document.getElementById('offerFieldCash');
    var tradePanel = document.getElementById('offerFieldTrade');
    var cashInput = document.getElementById('cashInput');
    var tradeSelect = document.getElementById('tradeSelect');

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        tabs.forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');
        var mode = tab.getAttribute('data-target');

        if (mode === 'cash') {
          cashPanel.style.display = '';
          tradePanel.style.display = 'none';
          if (tradeSelect) tradeSelect.value = '0';
        } else if (mode === 'trade') {
          cashPanel.style.display = 'none';
          tradePanel.style.display = '';
          cashInput.value = '0';
        } else {
          cashPanel.style.display = '';
          tradePanel.style.display = '';
        }
      });
    });
  });
  </script>
</main>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>