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
    $condition = $_POST['condition_status'] ?? '';
    $city     = trim($_POST['city'] ?? '');

    if (!array_key_exists($condition, yz_conditions())) $condition = null;

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
            'INSERT INTO items (user_id, claim_id, name, category, serial_number, notes, photo_path, condition_status, city, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "owned")'
        );
        // Insert with a placeholder claim_id first, then fill in the real one using the new row id.
        $stmt->execute([$user['id'], 'PENDING', $name, $category, $serial ?: null, $notes ?: null, $photoFilename, $condition, $city ?: null]);
        $newId = (int)$pdo->lastInsertId();
        $claimId = generate_claim_id($category, $newId);
        $pdo->prepare('UPDATE items SET claim_id = ? WHERE id = ?')->execute([$claimId, $newId]);
        $pdo->commit();

        flash_set('success', "\"$name\" is registered. Claim ID: $claimId");
        header('Location: dashboard.php');
        exit;
    }
}
$pageTitle = 'Register an item — YONZON CLAIM';
$activeNav = '';
require __DIR__ . '/includes/header-dash.php';
?>

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

  <form method="post" enctype="multipart/form-data" class="form-card js-validate" novalidate>
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
      <label class="field">
        <span>Condition <em>(optional)</em></span>
        <select name="condition_status">
          <option value="">Not specified</option>
          <?php foreach (yz_conditions() as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= (($_POST['condition_status'] ?? '') === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <label class="field">
      <span>City <em>(optional — helps local buyers find it)</em></span>
      <input type="text" name="city" value="<?= e($_POST['city'] ?? '') ?>" maxlength="100" placeholder="e.g. Cebu City">
    </label>

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

<?php require __DIR__ . '/includes/footer-dash.php'; ?>