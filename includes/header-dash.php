<?php
/**
 * includes/header-dash.php
 * Shared head + top nav for every logged-in page. Include this AFTER
 * require_login() and $user = current_user() have already run, and
 * AFTER setting:
 *   $pageTitle  (string)              — shown in the browser tab
 *   $activeNav  (string, optional)    — one of: dashboard, browse, offers, profile
 * This is the fix for the 13-file copy-pasted nav block: change the nav
 * ONE time here and every page that includes this file updates at once.
 */
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'YONZON CLAIM') ?></title>
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
        <a href="dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="browse.php" class="<?= $activeNav === 'browse' ? 'active' : '' ?>">Marketplace</a>
        <a href="offers.php" class="<?= $activeNav === 'offers' ? 'active' : '' ?>">Offers<?= pending_offer_badge($pdo, $user['id']) ?></a>
        <a href="profile.php" class="<?= $activeNav === 'profile' ? 'active' : '' ?>">Profile</a>
        <?php if (is_admin($pdo, $user['id'])): ?>
          <a href="admin.php" class="<?= $activeNav === 'admin' ? 'active' : '' ?>">Admin</a>
        <?php endif; ?>
      </nav>
      <div class="dash-user">
        <span><?= e($user['name']) ?></span>
        <a class="btn btn-ghost" href="logout.php">Log out</a>
      </div>
    </div>
  </div>
</div>

<main class="dash-wrap dash-main">
