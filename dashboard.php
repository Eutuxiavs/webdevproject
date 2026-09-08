<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

$stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();

// Most recent ownership transfer per item, if any (so we can show "Acquired via trade" etc).
$acquiredNotes = [];
if ($items) {
    $itemIds = array_column($items, 'id');
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT h.item_id, h.transferred_at, u.name AS previous_owner_name
         FROM ownership_history h
         JOIN users u ON u.id = h.previous_owner_id
         WHERE h.item_id IN ($placeholders) AND h.new_owner_id = ?
         ORDER BY h.transferred_at DESC"
    );
    $stmt->execute([...$itemIds, $user['id']]);
    foreach ($stmt->fetchAll() as $row) {
        if (!isset($acquiredNotes[$row['item_id']])) { // keep only the most recent per item
            $acquiredNotes[$row['item_id']] = $row;
        }
    }
}

$counts = ['owned' => 0, 'for_sale' => 0, 'lost' => 0, 'warranty' => 0, 'reserved' => 0, 'sold' => 0];
foreach ($items as $it) { $counts[$it['status']] = ($counts[$it['status']] ?? 0) + 1; }

$success = flash_get('success');
$error   = flash_get('error');
$pageTitle = 'Dashboard — YONZON CLAIM';
$activeNav = 'dashboard';
require __DIR__ . '/includes/header-dash.php';
?>


  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">
        <?= count($items) ?> filed &middot;
        <?= $counts['for_sale'] ?> for sale &middot;
        <?= $counts['lost'] ?> lost &middot;
        <?= $counts['warranty'] ?> under warranty
      </div>
      <h1 class="dash-title">Your registry.</h1>
    </div>
    <div class="dash-actions">
      <a class="btn btn-primary" href="claim-add.php">+ Register an item</a>
    </div>
  </div>

  <section class="dash-claims">
    <?php if (!$items): ?>
      <div class="dash-empty">
        <p>Nothing filed yet.</p>
        <a class="btn btn-ghost" href="claim-add.php">Register your first item</a>
      </div>
    <?php else: ?>
      <div class="inv-grid">
        <?php foreach ($items as $item): ?>
          <div class="inv-card">
            <div class="inv-photo">
              <?php if ($item['photo_path']): ?>
                <img src="<?= e(UPLOAD_URL . $item['photo_path']) ?>" alt="<?= e($item['name']) ?>">
              <?php else: ?>
                <?= yz_icon(category_icon_key($item['category'])) ?>
              <?php endif; ?>
            </div>
            <div class="inv-body">
              <div class="inv-cat"><?= e($item['category']) ?></div>
              <div class="inv-name"><?= e($item['name']) ?></div>
              <?php if (isset($acquiredNotes[$item['id']])): ?>
                <div class="inv-acquired">Acquired from <?= e($acquiredNotes[$item['id']]['previous_owner_name']) ?> on <?= e(date('d M Y', strtotime($acquiredNotes[$item['id']]['transferred_at']))) ?></div>
              <?php endif; ?>
              <div class="inv-meta">
                <span class="inv-id mono"><?= e($item['claim_id']) ?></span>
                <span class="reg-status <?= status_class($item['status']) ?>"><?= status_label($item['status']) ?></span>
              </div>
              <?php if ($item['status'] === 'for_sale' && $item['price']): ?>
                <div class="inv-price">$<?= number_format((float)$item['price'], 2) ?></div>
              <?php endif; ?>
              <div class="inv-actions">
                <?php if ($item['status'] === 'owned'): ?>
                  <a href="claim-sell.php?id=<?= (int)$item['id'] ?>">List for sale</a>
                  <form class="inline-action" method="post" action="claim-status.php" data-confirm="Mark this item as lost? Its status will change publicly.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="lost">
                    <button type="submit">Report lost</button>
                  </form>
                <?php elseif ($item['status'] === 'for_sale'): ?>
                  <?php if (!$item['is_featured'] || strtotime($item['featured_until']) < time()): ?>
                    <form class="inline-action" method="post" action="claim-status.php" data-confirm="Feature this listing for $<?= number_format(FEATURED_LISTING_FEE, 2) ?>? It'll be pinned to the top of the marketplace for <?= FEATURED_LISTING_DAYS ?> days. (No real payment is processed — this is a simulated charge for demo purposes.)" data-confirm-title="Feature this listing?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                      <input type="hidden" name="action" value="feature">
                      <button type="submit">⭐ Feature ($<?= number_format(FEATURED_LISTING_FEE, 2) ?>)</button>
                    </form>
                  <?php else: ?>
                    <span class="offer-waiting">Featured until <?= e(date('d M', strtotime($item['featured_until']))) ?></span>
                  <?php endif; ?>
                  <form class="inline-action" method="post" action="claim-status.php" data-confirm="Mark this item as sold? It will move out of your active listings." data-confirm-title="Mark as sold?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="sold">
                    <button type="submit">Mark sold</button>
                  </form>
                  <form class="inline-action" method="post" action="claim-status.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="cancel_sale">
                    <button type="submit">Cancel listing</button>
                  </form>
                <?php elseif ($item['status'] === 'lost'): ?>
                  <form class="inline-action" method="post" action="claim-status.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="found">
                    <button type="submit">Mark found</button>
                  </form>
                <?php elseif ($item['status'] === 'reserved'): ?>
                  <a href="offers.php">View offer &rarr;</a>
                <?php endif; ?>
                <form class="inline-action" method="post" action="claim-status.php" data-confirm="This permanently removes the claim and its photo. This can't be undone." data-confirm-title="Delete this item?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="dash-danger">Delete</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>