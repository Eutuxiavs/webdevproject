<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Privacy Policy — YONZON CLAIM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,340;0,9..144,480;0,9..144,600;1,9..144,460&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
</head>
<body class="dash-body">

<div class="letterhead-top dash-letterhead">
  <div class="dash-wrap">
    <div class="lh-main" style="padding:18px 0;">
      <a class="mark" href="<?= $user ? 'dashboard.php' : 'index.php' ?>">
        <?php brand_mark(34); ?>
        <div class="mark-word"><div class="a">YONZON</div><div class="b">Claim Registry</div></div>
      </a>
    </div>
  </div>
</div>

<main class="dash-wrap dash-main" style="max-width:760px; margin:0 auto;">
  <h1 class="dash-title" style="margin-bottom:8px;">Privacy Policy</h1>
  <p class="dash-summary" style="margin-bottom:36px;">Last updated <?= date('F Y') ?></p>

  <div class="legal-body">
    <h3>What we store</h3>
    <p>Your name, email, a hashed password (never the password itself), and anything you choose to
    add: bio, business name, profile picture, and the items you register including their photos.</p>

    <h3>What we never expose</h3>
    <p>Serial numbers you register are stored but never shown to anyone but you. If someone scans a
    Lost item's record, they see a status and a way to message you — never your email, phone, or
    address directly.</p>

    <h3>Messages and offers</h3>
    <p>Chat messages and offer details are visible to the two people involved in that conversation or
    deal, and to nobody else.</p>

    <h3>Account deletion</h3>
    <p>You can delete your account at any time from your Profile page. This removes your registered
    items, listings, and messages associated with your account.</p>

    <h3>Cookies</h3>
    <p>We use a single session cookie to keep you logged in. No tracking or advertising cookies are
    used — this project doesn't run ads or share data with third parties.</p>

    <h3>Contact</h3>
    <p>This is a school project without a formal support channel; questions can be directed to the
    project's author.</p>
  </div>
</main>

</body>
</html>
