<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$errors = [];
$categories = yz_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $serial   = trim($_POST['serial_number'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');

    if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Enter an item name.';
    if (!in_array($category, $categories, true)) $errors[] = 'Choose a valid category.';

    $photoFilename = null;
    try {
        $photoFilename = handle_photo_upload('photo');
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    if (!$errors) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO items (user_id, claim_id, name, category, serial_number, notes, photo_path, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, "owned")'
        );
        // Insert with a placeholder claim_id first, then fill in the real one using the new row id.
        $stmt->execute([$user['id'], 'PENDING', $name, $category, $serial ?: null, $notes ?: null, $photoFilename]);
        $newId = (int)$pdo->lastInsertId();
        $claimId = generate_claim_id($category, $newId);
        $pdo->prepare('UPDATE items SET claim_id = ? WHERE id = ?')->execute([$claimId, $newId]);
        $pdo->commit();

        flash_set('success', "\"$name\" is registered. Claim ID: $claimId");
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register an item — YONZON CLAIM</title>
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
      <div class="dash-summary">Filing procedure &middot; Step 1 of 1</div>
      <h1 class="dash-title">Register an item.</h1>
    </div>
    <a class="btn btn-ghost" href="dashboard.php">&larr; Back to registry</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="form-card">
    <?= csrf_field() ?>

    <div class="form-row">
      <label class="field">
        <span>Item name</span>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required maxlength="150" placeholder="e.g. Leica Camera M11">
      </label>
      <label class="field">
        <span>Category</span>
        <select name="category" required>
          <option value="">Choose one</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat) ?>" <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div class="form-row">
      <label class="field">
        <span>Serial number <em>(optional)</em></span>
        <input type="text" name="serial_number" value="<?= e($_POST['serial_number'] ?? '') ?>" maxlength="100" placeholder="Kept private, never shown publicly">
      </label>
    </div>

    <label class="field">
      <span>Notes <em>(optional)</em></span>
      <textarea name="notes" rows="3" maxlength="2000" placeholder="Purchase details, condition, anything worth recording."><?= e($_POST['notes'] ?? '') ?></textarea>
    </label>

    <label class="field upload-field" id="photoField">
      <span>Photo <em>(optional — you can add one later when listing for sale)</em></span>
      <div class="upload-drop" id="uploadDrop">
        <img id="uploadPreview" class="upload-preview" alt="" style="display:none;">
        <div class="upload-placeholder" id="uploadPlaceholder">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M21 16l-5-5-4 4-3-3-6 6"/></svg>
          <span>Click to upload a photo</span>
        </div>
        <input type="file" name="photo" id="photoInput" accept="image/png, image/jpeg, image/webp">
      </div>
    </label>

    <button type="submit" class="btn btn-primary form-submit">File this claim</button>
  </form>
</main>

<?php require __DIR__ . '/includes/chat-widget.php'; ?>
<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>