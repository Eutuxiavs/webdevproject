<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_admin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}
verify_csrf();

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'dismiss_report':
        $reportId = (int)($_POST['report_id'] ?? 0);
        $pdo->prepare('UPDATE reports SET status = "dismissed" WHERE id = ?')->execute([$reportId]);
        flash_set('success', 'Report dismissed.');
        break;

    case 'remove_item':
        $reportId = (int)($_POST['report_id'] ?? 0);
        $itemId   = (int)($_POST['item_id'] ?? 0);

        $stmt = $pdo->prepare('SELECT photo_path FROM items WHERE id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if ($item && $item['photo_path'] && is_file(UPLOAD_DIR . $item['photo_path'])) {
            @unlink(UPLOAD_DIR . $item['photo_path']);
        }

        $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$itemId]);
        $pdo->prepare('UPDATE reports SET status = "reviewed" WHERE id = ?')->execute([$reportId]);
        flash_set('success', 'Item removed.');
        break;

    case 'suspend_user':
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        $reportId = (int)($_POST['report_id'] ?? 0);

        if ($targetId === current_user_id()) { break; } // can't suspend yourself

        $pdo->prepare('UPDATE users SET is_suspended = 1 WHERE id = ?')->execute([$targetId]);
        if ($reportId) {
            $pdo->prepare('UPDATE reports SET status = "reviewed" WHERE id = ?')->execute([$reportId]);
        }
        flash_set('success', 'User suspended.');
        break;

    case 'unsuspend_user':
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        $pdo->prepare('UPDATE users SET is_suspended = 0, failed_login_count = 0, locked_until = NULL WHERE id = ?')
            ->execute([$targetId]);
        flash_set('success', 'User unsuspended.');
        break;

    default:
        flash_set('error', 'Unknown action.');
}

header('Location: admin.php');
exit;
