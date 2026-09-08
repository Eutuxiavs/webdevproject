<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_admin($pdo);

$user = current_user();

$stats = [
    'users'    => (int)$pdo->query('SELECT COUNT(*) AS n FROM users')->fetch()['n'],
    'items'    => (int)$pdo->query('SELECT COUNT(*) AS n FROM items')->fetch()['n'],
    'deals'    => (int)$pdo->query('SELECT COUNT(*) AS n FROM offers WHERE status = "completed"')->fetch()['n'],
    'revenue'  => (float)($pdo->query('SELECT COALESCE(SUM(amount),0) AS n FROM platform_revenue')->fetch()['n']),
];

$stmt = $pdo->query(
    'SELECT r.*, reporter.name AS reporter_name, reported.name AS reported_user_name, i.name AS item_name
     FROM reports r
     JOIN users reporter ON reporter.id = r.reporter_id
     LEFT JOIN users reported ON reported.id = r.reported_user_id
     LEFT JOIN items i ON i.id = r.reported_item_id
     ORDER BY r.status = "open" DESC, r.created_at DESC
     LIMIT 30'
);
$reports = $stmt->fetchAll();

$stmt = $pdo->query('SELECT id, name, email, role, is_suspended, created_at FROM users ORDER BY created_at DESC LIMIT 30');
$users = $stmt->fetchAll();

$stmt = $pdo->query(
    'SELECT pr.*, u.name AS user_name FROM platform_revenue pr
     LEFT JOIN users u ON u.id = pr.user_id
     ORDER BY pr.created_at DESC LIMIT 15'
);
$revenueLog = $stmt->fetchAll();

$success = flash_get('success');
$pageTitle = 'Admin — YONZON CLAIM';
$activeNav = 'admin';
require __DIR__ . '/includes/header-dash.php';
?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">Site oversight</div>
      <h1 class="dash-title">Admin.</h1>
    </div>
  </div>

  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

  <div class="admin-stats">
    <div class="admin-stat"><div class="n"><?= $stats['users'] ?></div><div class="l">Users</div></div>
    <div class="admin-stat"><div class="n"><?= $stats['items'] ?></div><div class="l">Items registered</div></div>
    <div class="admin-stat"><div class="n"><?= $stats['deals'] ?></div><div class="l">Completed deals</div></div>
    <div class="admin-stat"><div class="n">$<?= number_format($stats['revenue'], 2) ?></div><div class="l">Platform revenue (simulated)</div></div>
  </div>

  <section>
    <div class="kicker">Moderation</div>
    <h2 class="sec-title" style="font-size:20px; margin-bottom:16px;">Reports</h2>

    <?php if (!$reports): ?>
      <div class="dash-empty"><p>No reports.</p></div>
    <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Reported</th><th>Reason</th><th>By</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
            <tr>
              <td>
                <?php if ($r['item_name']): ?>"<?= e($r['item_name']) ?>"<br><?php endif; ?>
                <span style="color:var(--paper-faint);"><?= e($r['reported_user_name'] ?? 'Unknown') ?></span>
              </td>
              <td style="max-width:260px;"><?= e($r['reason']) ?></td>
              <td><?= e($r['reporter_name']) ?></td>
              <td><span class="reg-status <?= $r['status'] === 'open' ? '' : 'sold' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
              <td>
                <?php if ($r['status'] === 'open'): ?>
                  <div style="display:flex; flex-direction:column; gap:6px;">
                    <form method="post" action="admin-action.php" data-confirm="Dismiss this report? It will be marked as handled with no further action." data-confirm-title="Dismiss this report?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="action" value="dismiss_report">
                      <button type="submit" class="report-link" style="text-decoration:none; border:1px solid var(--line); padding:4px 10px; border-radius:12px;">Dismiss</button>
                    </form>
                    <?php if ($r['reported_item_id']): ?>
                      <form method="post" action="admin-action.php" data-confirm="Remove this item permanently? This can't be undone." data-confirm-title="Remove this item?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="item_id" value="<?= (int)$r['reported_item_id'] ?>">
                        <input type="hidden" name="action" value="remove_item">
                        <button type="submit" class="dispute-link">Remove item</button>
                      </form>
                    <?php endif; ?>
                    <?php if ($r['reported_user_id']): ?>
                      <form method="post" action="admin-action.php" data-confirm="Suspend this user? They won't be able to log in until unsuspended." data-confirm-title="Suspend this user?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="target_user_id" value="<?= (int)$r['reported_user_id'] ?>">
                        <input type="hidden" name="action" value="suspend_user">
                        <button type="submit" class="dispute-link">Suspend user</button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span style="color:var(--paper-faint); font-size:11px;">Handled</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <section style="margin-top:48px;">
    <div class="kicker">Accounts</div>
    <h2 class="sec-title" style="font-size:20px; margin-bottom:16px;">Users</h2>

    <table class="admin-table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="admin-role <?= $u['role'] ?>"><?= e($u['role']) ?></span></td>
            <td><?= $u['is_suspended'] ? '<span class="reg-status lost">Suspended</span>' : '<span class="reg-status">Active</span>' ?></td>
            <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
            <td>
              <?php if ((int)$u['id'] !== $user['id']): ?>
                <?php if ($u['is_suspended']): ?>
                  <form method="post" action="admin-action.php" style="display:inline;" data-confirm="Unsuspend this user? They'll be able to log in again immediately." data-confirm-title="Unsuspend this user?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">
                    <input type="hidden" name="action" value="unsuspend_user">
                    <button type="submit" class="report-link" style="border:1px solid var(--line); padding:4px 10px; border-radius:12px; text-decoration:none;">Unsuspend</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="admin-action.php" style="display:inline;" data-confirm="Suspend this user? They won't be able to log in until unsuspended." data-confirm-title="Suspend this user?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">
                    <input type="hidden" name="action" value="suspend_user">
                    <button type="submit" class="dispute-link">Suspend</button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <span style="color:var(--paper-faint); font-size:11px;">You</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section style="margin-top:48px;">
    <div class="kicker">Monetization</div>
    <h2 class="sec-title" style="font-size:20px; margin-bottom:16px;">Recent revenue</h2>

    <?php if (!$revenueLog): ?>
      <div class="dash-empty"><p>No revenue yet. Revenue is logged automatically when a listing is featured or a cash deal completes.</p></div>
    <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Source</th><th>Amount</th><th>From</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($revenueLog as $r): ?>
            <tr>
              <td><?= $r['source'] === 'featured_listing' ? '⭐ Featured listing' : '💰 Transaction fee' ?></td>
              <td style="color:var(--accent-l); font-weight:600;">$<?= number_format((float)$r['amount'], 2) ?></td>
              <td><?= e($r['user_name'] ?? '—') ?></td>
              <td class="mono"><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>