<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$targetId = (int)($_GET['user'] ?? 0);

if ($targetId === $user['id']) {
    header('Location: profile.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, avatar_path, bio, business FROM users WHERE id = ?');
$stmt->execute([$targetId]);
$target = $stmt->fetch();

if (!$target) {
    flash_set('error', 'That user was not found.');
    header('Location: browse.php');
    exit;
}

$stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS n FROM reviews WHERE reviewee_id = ?');
$stmt->execute([$targetId]);
$ratingRow = $stmt->fetch();
$avgRating = $ratingRow['avg_rating'] ? (float)$ratingRow['avg_rating'] : null;
$ratingCount = (int)$ratingRow['n'];

$verified = is_verified_trader($pdo, $targetId);
$blocked = users_blocked($pdo, $user['id'], $targetId);

// This user's items currently for sale, publicly visible.
$stmt = $pdo->prepare('SELECT * FROM items WHERE user_id = ? AND status = "for_sale" ORDER BY updated_at DESC LIMIT 6');
$stmt->execute([$targetId]);
$listings = $stmt->fetchAll();

$success = flash_get('success');
$pageTitle = '' . e($target['name']) . ' — YONZON CLAIM';
$activeNav = '';
require __DIR__ . '/includes/header-dash.php';
?>

  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="profile-head">
    <div class="profile-avatar">
      <?php if ($target['avatar_path']): ?>
        <img src="<?= e(UPLOAD_URL . $target['avatar_path']) ?>" alt="<?= e($target['name']) ?>">
      <?php else: ?>
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"/></svg>
      <?php endif; ?>
    </div>
    <div>
      <div class="profile-name"><?= e($target['name']) ?> <?php if ($verified): ?><span class="verified-badge" title="Has completed at least one deal">&#10003; Verified Trader</span><?php endif; ?></div>
      <?php if ($target['business']): ?><div class="profile-business"><?= e($target['business']) ?></div><?php endif; ?>
      <?php if ($avgRating !== null): ?>
        <div class="profile-rating"><span class="stars"><?= star_display($avgRating) ?></span> <?= number_format($avgRating, 1) ?> (<?= $ratingCount ?> review<?= $ratingCount === 1 ? '' : 's' ?>)</div>
      <?php else: ?>
        <div class="dash-summary" style="margin-top:6px;">No reviews yet</div>
      <?php endif; ?>
      <?php if ($target['bio']): ?><p style="font-size:13px; color:var(--paper-dim); margin-top:12px; max-width:480px; line-height:1.6;"><?= nl2br(e($target['bio'])) ?></p><?php endif; ?>
    </div>
  </div>

  <form method="post" action="block-action.php" style="margin-bottom:44px;">
    <?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= (int)$target['id'] ?>">
    <?php if ($blocked): ?>
      <input type="hidden" name="action" value="unblock">
      <button type="submit" class="btn btn-ghost">Unblock this user</button>
    <?php else: ?>
      <input type="hidden" name="action" value="block">
      <button type="submit" class="btn btn-ghost" data-confirm-inline="1" onclick="return confirm('Block this user? They won\'t be able to message you or make offers, and you won\'t see theirs.');">Block this user</button>
    <?php endif; ?>
  </form>

  <section>
    <div class="kicker">Currently listed</div>
    <h2 class="sec-title" style="font-size:22px; margin-bottom:20px;">Items for sale by <?= e($target['name']) ?></h2>

    <?php if (!$listings): ?>
      <div class="dash-empty"><p>Nothing listed right now.</p></div>
    <?php else: ?>
      <div class="reg-grid">
        <?php foreach ($listings as $item): ?>
          <div class="reg-card">
            <div class="reg-image">
              <div class="reg-forsale">For Sale</div>
              <?php if ($item['photo_path']): ?>
                <img class="reg-photo" src="<?= e(UPLOAD_URL . $item['photo_path']) ?>" alt="<?= e($item['name']) ?>">
              <?php else: ?>
                <?= yz_icon(category_icon_key($item['category'])) ?>
              <?php endif; ?>
              <?php if ($item['price']): ?><div class="reg-price">$<?= number_format((float)$item['price'], 2) ?></div><?php endif; ?>
            </div>
            <div class="reg-content">
              <div class="reg-cat"><?= e($item['category']) ?></div>
              <div class="reg-name"><?= e($item['name']) ?></div>
              <?php if (!$blocked): ?>
                <a class="btn btn-primary" style="width:100%; text-align:center; margin-top:14px;" href="offer-create.php?item=<?= (int)$item['id'] ?>">Make Offer</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>