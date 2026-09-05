<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
verify_csrf();

$user   = current_user();
$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

// Ownership check — WHERE user_id = ? on every statement below is the
// actual authorization control, not just the require_login() call.
$stmt = $pdo->prepare('SELECT * FROM items WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
$item = $stmt->fetch();

if (!$item) {
    flash_set('error', "That item wasn't found in your registry.");
    header('Location: dashboard.php');
    exit;
}

switch ($action) {
    case 'lost':
        $pdo->prepare('UPDATE items SET status = "lost" WHERE id = ? AND user_id = ?')
            ->execute([$id, $user['id']]);
        flash_set('success', "\"{$item['name']}\" is now marked lost.");
        break;

    case 'found':
        $pdo->prepare('UPDATE items SET status = "owned" WHERE id = ? AND user_id = ?')
            ->execute([$id, $user['id']]);
        flash_set('success', "Good news — \"{$item['name']}\" is marked found.");
        break;

    case 'sold':
        $pdo->prepare('UPDATE items SET status = "sold" WHERE id = ? AND user_id = ?')
            ->execute([$id, $user['id']]);
        flash_set('success', "\"{$item['name']}\" is marked sold. Nice.");
        break;

    case 'cancel_sale':
        $pdo->prepare('UPDATE items SET status = "owned", price = NULL WHERE id = ? AND user_id = ?')
            ->execute([$id, $user['id']]);
        flash_set('success', "Listing for \"{$item['name']}\" was cancelled.");
        break;

    case 'delete':
        if ($item['photo_path'] && is_file(UPLOAD_DIR . $item['photo_path'])) {
            @unlink(UPLOAD_DIR . $item['photo_path']);
        }
        $pdo->prepare('DELETE FROM items WHERE id = ? AND user_id = ?')
            ->execute([$id, $user['id']]);
        flash_set('success', "\"{$item['name']}\" was removed from your registry.");
        break;

    default:
        flash_set('error', 'Unknown action.');
}

header('Location: dashboard.php');
exit;