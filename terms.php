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
<title>Terms of Service — YONZON CLAIM</title>
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
  <h1 class="dash-title" style="margin-bottom:8px;">Terms of Service</h1>
  <p class="dash-summary" style="margin-bottom:36px;">Last updated <?= date('F Y') ?></p>

  <div class="legal-body">
    <h3>1. What this is</h3>
    <p>YONZON CLAIM is a registry where you record items you own, and optionally list them for sale
    or trade to other users. This is a student/school project, not a commercial service.</p>

    <h3>2. Your account</h3>
    <p>You're responsible for keeping your password confidential and for anything that happens under
    your account. Don't share your login with anyone else.</p>

    <h3>3. Listings and transactions</h3>
    <p>YONZON CLAIM does not process payments and is not a party to any sale or trade between users.
    We provide a mutual-confirmation system so ownership only transfers when both sides agree it
    happened, but we cannot guarantee any buyer or seller will act in good faith. Meet safely, verify
    what you're buying before paying, and use the built-in chat to agree on payment method first.</p>

    <h3>4. Prohibited conduct</h3>
    <p>Don't list stolen items, don't impersonate someone else, don't harass other users, and don't use
    the platform for anything illegal. Violating this may get your account suspended.</p>

    <h3>5. No warranty</h3>
    <p>This service is provided "as is," as a school project, without warranty of any kind. We're not
    liable for any loss arising from its use.</p>

    <h3>6. Changes</h3>
    <p>These terms may change as the project develops. Continued use after a change means you accept
    the new terms.</p>
  </div>
</main>

</body>
</html>
