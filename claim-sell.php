<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM items WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
$item = $stmt->fetch();

if (!$item) {
    flash_set('error', "That item wasn't found in your registry.");
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $price = trim($_POST['price'] ?? '');
    if (!is_numeric($price) || (float)$price <= 0) {
        $errors[] = 'Enter a valid asking price.';
    }

    $photoFilename = $item['photo_path'];
    try {
        $uploaded = handle_photo_upload('photo');
        if ($uploaded) $photoFilename = $uploaded;
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    // A real photo (not the icon placeholder) is required to publish a listing.
    if (!$photoFilename) {
        $errors[] = 'Add a real photo of the item before listing it for sale.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE items SET status = "for_sale", price = ?, photo_path = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([(float)$price, $photoFilename, $item['id'], $user['id']]);
        flash_set('success', "\"{$item['name']}\" is now listed for sale.");
        header('Location: dashboard.php');
        exit;
    }
}
$pageTitle = 'List for sale — YONZON CLAIM';
$activeNav = '';
require __DIR__ . '/includes/header-dash.php';
?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">Claim <?= e($item['claim_id']) ?></div>
      <h1 class="dash-title">List &ldquo;<?= e($item['name']) ?>&rdquo; for sale.</h1>
    </div>
    <a class="btn btn-ghost" href="dashboard.php">&larr; Back to registry</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error">
      <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="form-card js-validate" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

    <label class="field upload-field">
      <span>Photo of the actual item <em>(required — buyers need to see the real thing)</em></span>
      <div class="upload-drop" id="uploadDrop">
        <img id="uploadPreview" class="upload-preview" alt="" src="<?= $item['photo_path'] ? e(UPLOAD_URL . $item['photo_path']) : '' ?>" style="<?= $item['photo_path'] ? '' : 'display:none;' ?>">
        <div class="upload-placeholder" id="uploadPlaceholder" style="<?= $item['photo_path'] ? 'display:none;' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M21 16l-5-5-4 4-3-3-6 6"/></svg>
          <span>Click to upload a photo</span>
        </div>
        <input type="file" name="photo" id="photoInput" accept="image/png, image/jpeg, image/webp">
      </div>
    </label>

    <label class="field">
      <span>Asking price (USD)</span>
      <input type="number" name="price" step="0.01" min="0.01" value="<?= e($_POST['price'] ?? '') ?>" required placeholder="1450.00">
    </label>

    <button type="submit" class="btn btn-primary form-submit">Publish listing</button>
  </form>
</main>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>