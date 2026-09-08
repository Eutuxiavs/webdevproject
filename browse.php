<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$categories = yz_categories();

$statusFilter = $_GET['status'] ?? 'all';
$validStatuses = ['all', 'for_sale', 'lost'];
if (!in_array($statusFilter, $validStatuses, true)) $statusFilter = 'all';

$categoryFilter = $_GET['category'] ?? '';
if (!in_array($categoryFilter, $categories, true)) $categoryFilter = '';

$search = trim($_GET['q'] ?? '');

$perPage = 9;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ' WHERE 1=1';
$params = [];

if ($statusFilter === 'all') {
    $where .= ' AND i.status IN ("for_sale", "lost")'; // only publicly-relevant statuses
} else {
    $where .= ' AND i.status = ?';
    $params[] = $statusFilter;
}
if ($categoryFilter !== '') {
    $where .= ' AND i.category = ?';
    $params[] = $categoryFilter;
}
if ($search !== '') {
    $where .= ' AND (i.name LIKE ? OR i.claim_id LIKE ? OR i.city LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$countStmt = $pdo->prepare('SELECT COUNT(*) AS n FROM items i' . $where);
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetch()['n'];
$totalPages = max(1, (int)ceil($totalCount / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT i.*, u.name AS owner_name FROM items i JOIN users u ON u.id = i.user_id'
     . $where . ' ORDER BY (i.is_featured = 1 AND i.featured_until > NOW()) DESC, i.updated_at DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

function filter_url(string $status, string $category, string $search = '', int $page = 1): string {
    $q = [];
    if ($status !== 'all') $q['status'] = $status;
    if ($category !== '') $q['category'] = $category;
    if ($search !== '') $q['q'] = $search;
    if ($page > 1) $q['page'] = $page;
    return 'browse.php' . ($q ? '?' . http_build_query($q) : '');
}
$recentIds = get_recently_viewed_ids();
$recentItems = [];
if ($recentIds) {
    $placeholders = implode(',', array_fill(0, count($recentIds), '?'));
    $stmt = $pdo->prepare("SELECT id, name, category, photo_path, status, claim_id FROM items WHERE id IN ($placeholders) AND status IN ('for_sale','lost')");
    $stmt->execute($recentIds);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) { $byId[$row['id']] = $row; }
    foreach ($recentIds as $id) { if (isset($byId[$id])) { $recentItems[] = $byId[$id]; } } // preserve most-recent-first order
}

$pageTitle = 'Marketplace — YONZON CLAIM';
$activeNav = 'browse';
require __DIR__ . '/includes/header-dash.php';
?>

  <div class="dash-head">
    <div>
      <div class="dash-summary">Buy, sell, and help reunite lost items</div>
      <h1 class="dash-title">Marketplace.</h1>
    </div>
    <a class="btn btn-primary" href="claim-add.php">+ Register an item</a>
  </div>

  <?php if ($recentItems): ?>
    <div class="recent-strip">
      <div class="recent-label">Recently viewed</div>
      <div class="recent-row">
        <?php foreach ($recentItems as $r): ?>
          <a href="offer-create.php?item=<?= (int)$r['id'] ?>" class="recent-item">
            <div class="recent-photo">
              <?php if ($r['photo_path']): ?>
                <img src="<?= e(UPLOAD_URL . $r['photo_path']) ?>" alt="">
              <?php else: ?>
                <?= yz_icon(category_icon_key($r['category'])) ?>
              <?php endif; ?>
            </div>
            <span><?= e($r['name']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="filter-bar">
    <a class="filter-pill <?= $statusFilter === 'all' ? 'active' : '' ?>" href="<?= e(filter_url('all', $categoryFilter)) ?>">All Listings</a>
    <a class="filter-pill <?= $statusFilter === 'for_sale' ? 'active' : '' ?>" href="<?= e(filter_url('for_sale', $categoryFilter)) ?>">For Sale</a>
    <a class="filter-pill <?= $statusFilter === 'lost' ? 'active' : '' ?>" href="<?= e(filter_url('lost', $categoryFilter)) ?>">Lost &amp; Found</a>

    <form method="get" style="margin-left:auto; display:flex; gap:10px;">
      <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name, claim ID, or city…" class="filter-select" style="min-width:200px;">
      <select name="category" class="filter-select" onchange="this.form.submit()">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= e($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-ghost">Search</button>
    </form>
  </div>

  <div class="filter-count"><?= $totalCount ?> item<?= $totalCount === 1 ? '' : 's' ?> found<?= $search !== '' ? ' for "' . e($search) . '"' : '' ?></div>

  <?php if (!$listings): ?>
    <div class="dash-empty">
      <p>Nothing matches these filters yet.</p>
      <a class="btn btn-ghost" href="browse.php">Clear filters</a>
    </div>
  <?php else: ?>
    <div class="reg-grid">
      <?php foreach ($listings as $item): ?>
        <div class="reg-card <?= ($item['is_featured'] && strtotime($item['featured_until']) > time()) ? 'featured-card' : '' ?>">
          <div class="reg-image">
            <?php if ($item['is_featured'] && strtotime($item['featured_until']) > time()): ?>
              <div class="reg-featured">⭐ Featured</div>
            <?php elseif ($item['status'] === 'for_sale'): ?>
              <div class="reg-forsale">For Sale</div>
            <?php endif; ?>
            <?php if ($item['photo_path']): ?>
              <img class="reg-photo" src="<?= e(UPLOAD_URL . $item['photo_path']) ?>" alt="<?= e($item['name']) ?>">
            <?php else: ?>
              <?= yz_icon(category_icon_key($item['category'])) ?>
            <?php endif; ?>
            <?php if ($item['status'] === 'for_sale' && $item['price']): ?>
              <div class="reg-price">$<?= number_format((float)$item['price'], 2) ?></div>
            <?php endif; ?>
          </div>
          <div class="reg-content">
            <div class="reg-cat"><?= e($item['category']) ?><?php if ($item['condition_status']): ?> &middot; <?= e(condition_label($item['condition_status'])) ?><?php endif; ?></div>
            <div class="reg-name"><?= e($item['name']) ?></div>
            <?php if ($item['city']): ?><div class="reg-city">📍 <?= e($item['city']) ?></div><?php endif; ?>
            <div class="reg-meta">
              <span class="reg-id mono"><?= e($item['claim_id']) ?></span>
              <span class="reg-status <?= status_class($item['status']) ?>"><?= status_label($item['status']) ?></span>
            </div>
            <div class="reg-date mono">By <a href="profile-view.php?user=<?= (int)$item['user_id'] ?>" style="color:inherit; text-decoration:underline;"><?= e($item['owner_name']) ?></a></div>

            <?php if ((int)$item['user_id'] !== $user['id']): ?>
              <?php if (users_blocked($pdo, $user['id'], (int)$item['user_id'])): ?>
                <div style="margin-top:14px; font-size:11px; color:var(--paper-faint); text-align:center;">Unavailable</div>
              <?php else: ?>
                <?php if ($item['status'] === 'for_sale'): ?>
                  <div style="display:flex; gap:8px; margin-top:14px;">
                    <a class="btn btn-primary" style="flex:1; text-align:center;" href="offer-create.php?item=<?= (int)$item['id'] ?>">Make Offer</a>
                    <a class="btn btn-ghost" style="flex:1; text-align:center;" href="start-conversation.php?item=<?= (int)$item['id'] ?>">Message</a>
                  </div>
                <?php else: ?>
                  <a class="btn btn-ghost" style="width:100%; text-align:center; margin-top:14px;" href="start-conversation.php?item=<?= (int)$item['id'] ?>">I Found This</a>
                <?php endif; ?>
                <div style="text-align:center; margin-top:8px;">
                  <a href="report-submit.php?item=<?= (int)$item['id'] ?>" class="report-link">Report this listing</a>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div style="margin-top:14px; font-size:11px; color:var(--paper-faint); text-align:center;">This is your item</div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a class="page-pill <?= $p === $page ? 'active' : '' ?>" href="<?= e(filter_url($statusFilter, $categoryFilter, $search, $p)) ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer-dash.php'; ?>