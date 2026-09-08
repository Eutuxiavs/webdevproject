<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$userId = current_user_id();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim($_POST['name'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $business = trim($_POST['business'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Enter your name.';
    if (mb_strlen($bio) > 1000) $errors[] = 'Bio is too long (max 1000 characters).';
    if (mb_strlen($business) > 150) $errors[] = 'Business name is too long.';

    $avatarFilename = $profile['avatar_path'];
    try {
        $uploaded = handle_photo_upload('avatar');
        if ($uploaded) $avatarFilename = $uploaded;
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE users SET name = ?, bio = ?, business = ?, avatar_path = ? WHERE id = ?');
        $stmt->execute([$name, $bio ?: null, $business ?: null, $avatarFilename, $userId]);
        flash_set('success', 'Profile updated.');
        header('Location: profile.php');
        exit;
    }
}

$success = flash_get('success');

// Quick stats for the profile header.
$stmt = $pdo->prepare('SELECT status, COUNT(*) AS n FROM items WHERE user_id = ? GROUP BY status');
$stmt->execute([$userId]);
$statRows = $stmt->fetchAll();
$statCounts = ['owned' => 0, 'for_sale' => 0, 'lost' => 0, 'warranty' => 0, 'reserved' => 0, 'sold' => 0];
foreach ($statRows as $row) { $statCounts[$row['status']] = (int)$row['n']; }
$totalItems = array_sum($statCounts);

$stmt = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS n FROM reviews WHERE reviewee_id = ?');
$stmt->execute([$userId]);
$ratingRow = $stmt->fetch();
$avgRating = $ratingRow['avg_rating'] ? (float)$ratingRow['avg_rating'] : null;
$ratingCount = (int)$ratingRow['n'];
$pageTitle = 'Profile — YONZON CLAIM';
$activeNav = 'profile';
require __DIR__ . '/includes/header-dash.php';
?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">Filed under <?= e($profile['email']) ?></div>
      <h1 class="dash-title">Your profile.</h1>
    </div>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <div class="profile-head">
    <div class="profile-avatar">
      <?php if ($profile['avatar_path']): ?>
        <img src="<?= e(UPLOAD_URL . $profile['avatar_path']) ?>" alt="<?= e($profile['name']) ?>">
      <?php else: ?>
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-7 8-7s8 3 8 7"/></svg>
      <?php endif; ?>
    </div>
    <div>
      <div class="profile-name"><?= e($profile['name']) ?> <?php if (is_verified_trader($pdo, $userId)): ?><span class="verified-badge">&#10003; Verified Trader</span><?php endif; ?></div>
      <?php if ($profile['business']): ?><div class="profile-business"><?= e($profile['business']) ?></div><?php endif; ?>
      <?php if ($avgRating !== null): ?>
        <div class="profile-rating"><span class="stars"><?= star_display($avgRating) ?></span> <?= number_format($avgRating, 1) ?> (<?= $ratingCount ?> review<?= $ratingCount === 1 ? '' : 's' ?>)</div>
      <?php else: ?>
        <div class="dash-summary" style="margin-top:6px;">No reviews yet</div>
      <?php endif; ?>
      <div class="dash-summary" style="margin-top:8px;"><?= $totalItems ?> filed &middot; <?= $statCounts['for_sale'] ?> for sale &middot; <?= $statCounts['lost'] ?> lost</div>
    </div>
  </div>

  <form method="post" enctype="multipart/form-data" class="form-card">
    <?= csrf_field() ?>

    <label class="field upload-field">
      <span>Profile picture <em>(optional)</em></span>
      <div class="upload-drop round" id="uploadDrop">
        <img id="uploadPreview" class="upload-preview" alt="" src="<?= $profile['avatar_path'] ? e(UPLOAD_URL . $profile['avatar_path']) : '' ?>" style="<?= $profile['avatar_path'] ? '' : 'display:none;' ?>">
        <div class="upload-placeholder" id="uploadPlaceholder" style="<?= $profile['avatar_path'] ? 'display:none;' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M21 16l-5-5-4 4-3-3-6 6"/></svg>
        </div>
        <input type="file" name="avatar" id="photoInput" accept="image/png, image/jpeg, image/webp">
      </div>
    </label>

    <div class="form-row">
      <label class="field">
        <span>Full name</span>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? $profile['name']) ?>" required maxlength="100">
      </label>
      <label class="field">
        <span>Business / related to <em>(optional)</em></span>
        <input type="text" name="business" value="<?= e($_POST['business'] ?? $profile['business'] ?? '') ?>" maxlength="150" placeholder="e.g. Yonzon Vintage Cameras">
      </label>
    </div>

    <label class="field">
      <span>Bio <em>(optional)</em></span>
      <textarea name="bio" rows="4" maxlength="1000" placeholder="A short line about you or what you collect/sell."><?= e($_POST['bio'] ?? $profile['bio'] ?? '') ?></textarea>
    </label>

    <button type="submit" class="btn btn-primary form-submit">Save profile</button>
  </form>

  <div style="margin-top:40px; padding-top:24px; border-top:1px solid var(--line-soft);">
    <a href="delete-account.php" style="color:#e88686; font-size:12.5px;">Delete my account</a>
  </div>
</main>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>