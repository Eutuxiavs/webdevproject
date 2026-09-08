<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$user = current_user();

// "Live examples" grid — six most recent items across all users, any status.
$stmt = $pdo->query(
    'SELECT i.*, u.name AS owner_name FROM items i
     JOIN users u ON u.id = i.user_id
     ORDER BY i.created_at DESC LIMIT 6'
);
$registryItems = $stmt->fetchAll();

// Featured marketplace listing — most recently listed "for sale" item.
$stmt = $pdo->query(
    'SELECT i.*, u.name AS owner_name FROM items i
     JOIN users u ON u.id = i.user_id
     WHERE i.status = "for_sale" ORDER BY i.updated_at DESC LIMIT 1'
);
$featuredListing = $stmt->fetch();

$currentYear = date('Y');
$registerHref = $user ? 'claim-add.php' : 'register.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>YONZON CLAIM — Registry</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,340;0,9..144,480;0,9..144,600;1,9..144,460&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
</head>
<body>

<svg width="0" height="0" style="position:absolute">
  <defs>
    <linearGradient id="silverGrad" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#f4f3ef"/><stop offset="45%" stop-color="#8484f0"/><stop offset="100%" stop-color="#e8e7e3"/>
    </linearGradient>
  </defs>
</svg>

<div class="letterhead-top">
  <div class="wrap">
    <div class="lh-strip">
      <span>Ownership Registry</span>
      <span>Est. Verified Records</span>
    </div>
    <div class="lh-main">
      <div class="mark">
        <?php brand_mark(34); ?>
        <div class="mark-word"><div class="a">YONZON</div><div class="b">Claim Registry</div></div>
      </div>
      <div class="regno">
        <div class="l1">Filed under</div>
        <div class="l2 mono">REG. NO. 0001 — <?= e($currentYear) ?></div>
      </div>
    </div>
    <nav class="linkrow">
      <div class="navlinks">
        <a href="#certificate">Certificate</a>
        <a href="#ledger">The process</a>
        <a href="#registry">Live examples</a>
        <a href="#market">Marketplace</a>
        <a href="#notice">Lost &amp; found</a>
        <a href="#directory">The system</a>
      </div>
      <div class="navcta">
        <?php if ($user): ?>
          <a class="loginlink" href="dashboard.php">Dashboard</a>
          <a class="btn btn-primary" href="claim-add.php">Register an item</a>
        <?php else: ?>
          <a class="loginlink" href="login.php">Log in</a>
          <a class="btn btn-primary" href="register.php">Register an item</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</div>

<header class="hero">
  <div class="glow-field"></div>
  <div class="wrap">
    <div class="hero-grid">
      <div class="hero-copy">
        <div class="file-label">Filing No. 0001 · Category: Founding Statement</div>
        <h1 class="headline">Proof that<br>it belongs<br><em>to you.</em></h1>
        <p class="hero-lede">Register what you own, and YONZON keeps the record — a verifiable claim, a certificate, and a way for a stranger to reach you without ever seeing your name.</p>
        <div class="hero-ctas">
          <a class="btn btn-primary" href="<?= e($registerHref) ?>">Register your first item</a>
          <a class="btn btn-ghost" href="#certificate">Read a certificate</a>
        </div>
      </div>
      <div class="hero-mark">
        <img src="<?= e(asset_path('img/logo-diamond.png')) ?>" alt="" style="width:100%; max-width:260px; margin:0 auto; display:block; opacity:0.9;">
      </div>
    </div>
  </div>
</header>

<section class="cert-band" id="certificate">
  <div class="engrave"></div>
  <div class="wrap cert-inner">
    <div class="cert-eyebrow">Exhibit A</div>
    <h2 class="cert-heading">What a registered claim looks like.</h2>

    <div class="certificate reveal">
      <div class="cert-topbar">
        <div class="l">
          <svg viewBox="0 0 40 40" fill="none"><path d="M20 3L36 20L20 37L4 20L20 3Z" stroke="url(#silverGrad)" stroke-width="1"/><path d="M12 12L28 28M28 12L12 28" stroke="url(#silverGrad)" stroke-width="1"/><path d="M20 3V37" stroke="url(#silverGrad)" stroke-width="1"/></svg>
          <div class="t">YONZON CLAIM</div>
        </div>
        <div class="r">Certificate of Ownership</div>
      </div>

      <div class="cert-body">
        <div class="obj">Leica Camera M11</div>
        <p class="stmt">This certifies that the item described below has been recorded under a verified YONZON account, with supporting proof of purchase held in confidence.</p>
      </div>

      <div class="cert-fields">
        <div class="f"><div class="k">Owner</div><div class="v">J. Yonzon</div></div>
        <div class="f"><div class="k">Claim ID</div><div class="v mono">YZ-CM11-00421</div></div>
        <div class="f"><div class="k">Serial No.</div><div class="v mono">••••••••421</div></div>
        <div class="f"><div class="k">Registered</div><div class="v mono">10 AUG 2026</div></div>
      </div>

      <div class="cert-foot">
        <div class="qr" aria-hidden="true">
          <i></i><i></i><i class="off"></i><i></i><i></i>
          <i></i><i class="off"></i><i></i><i class="off"></i><i></i>
          <i></i><i></i><i></i><i></i><i class="off"></i>
          <i class="off"></i><i></i><i class="off"></i><i></i><i></i>
          <i></i><i class="off"></i><i></i><i></i><i class="off"></i>
        </div>
        <div class="seal-wrap">
          <img src="<?= e(asset_path('img/logo-diamond.png')) ?>" alt="" style="width:64px; height:64px; object-fit:contain; display:block;">
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ledger" id="ledger">
  <div class="wrap">
    <div class="sec-head reveal">
      <div>
        <div class="kicker">Filing procedure</div>
        <h2 class="sec-title">Five entries make a record.</h2>
      </div>
      <p class="sec-lede">The order is fixed on purpose — each entry becomes evidence for the one after it.</p>
    </div>

    <div class="ledger-table">
      <div class="lrow"><div class="rn">I</div><div class="name">Item</div><div class="desc">Name it, choose a category — electronics, timepieces, cameras, and beyond.</div></div>
      <div class="lrow"><div class="rn">II</div><div class="name">Photos</div><div class="desc">Original photos, timestamped the moment the claim is filed.</div></div>
      <div class="lrow"><div class="rn">III</div><div class="name">Serial</div><div class="desc">Serial number recorded and encrypted — visible only to you.</div></div>
      <div class="lrow"><div class="rn">IV</div><div class="name">Receipt</div><div class="desc">Purchase receipt or warranty document, attached as supporting proof.</div></div>
      <div class="lrow"><div class="rn">V</div><div class="name">Details</div><div class="desc">Review and confirm. Your Claim ID and QR code are issued instantly.</div></div>
    </div>
  </div>
</section>

<section class="registry" id="registry">
  <div class="wrap">
    <div class="sec-head reveal">
      <div>
        <div class="kicker">Live examples</div>
        <h2 class="sec-title">A few things already filed with YONZON.</h2>
      </div>
      <p class="sec-lede">Real claims, real categories — every item gets the same certificate, the same QR code, the same quiet protection.</p>
    </div>

    <?php if (!$registryItems): ?>
      <div class="dash-empty" style="margin-top:40px;">
        <p>Nothing registered yet — be the first.</p>
        <a class="btn btn-ghost" href="<?= e($registerHref) ?>">Register an item</a>
      </div>
    <?php else: ?>
      <div class="reg-grid">
        <?php foreach ($registryItems as $item): ?>
          <div class="reg-card reveal">
            <div class="reg-image">
              <?php if ($item['status'] === 'for_sale'): ?>
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
              <div class="reg-cat"><?= e($item['category']) ?></div>
              <div class="reg-name"><?= e($item['name']) ?></div>
              <div class="reg-meta">
                <span class="reg-id mono"><?= e($item['claim_id']) ?></span>
                <span class="reg-status <?= status_class($item['status']) ?>"><?= status_label($item['status']) ?></span>
              </div>
              <div class="reg-date mono">Registered <?= e(date('d M Y', strtotime($item['created_at']))) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="market" id="market">
  <div class="wrap">
    <div class="sec-head reveal">
      <div>
        <div class="kicker">Sell what's verified</div>
        <h2 class="sec-title">List it. Buyers can confirm you're the real owner — before they pay.</h2>
      </div>
      <p class="sec-lede">Second-hand sales carry one nagging question: is the seller who they say they are? A YONZON listing answers it — ownership was verified at registration, and the record has been quiet ever since.</p>
    </div>

    <?php if (!$featuredListing): ?>
      <div class="dash-empty" style="margin-top:40px;">
        <p>No listings yet — list something you own to see it featured here.</p>
        <?php if ($user): ?>
          <a class="btn btn-ghost" href="dashboard.php">Go to your registry</a>
        <?php else: ?>
          <a class="btn btn-ghost" href="register.php">Create an account</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="market-card reveal">
        <div class="market-image">
          <div class="reg-forsale">For Sale</div>
          <?php if ($featuredListing['photo_path']): ?>
            <img class="reg-photo" src="<?= e(UPLOAD_URL . $featuredListing['photo_path']) ?>" alt="<?= e($featuredListing['name']) ?>">
          <?php else: ?>
            <?= yz_icon(category_icon_key($featuredListing['category'])) ?>
          <?php endif; ?>
          <div class="reg-price" style="font-size:20px; padding:5px 14px;">$<?= number_format((float)$featuredListing['price'], 2) ?></div>
        </div>
        <div class="market-info">
          <div class="mkt-kicker">Verified Listing</div>
          <div class="mkt-title"><?= e($featuredListing['name']) ?></div>
          <p class="mkt-desc">Listed by a verified YONZON owner. Ownership was confirmed at registration and hasn't changed hands since — the full record is available to any interested buyer.</p>
          <div class="mkt-rows">
            <div class="mkt-row"><span class="k">Seller</span><span class="v"><?= e($featuredListing['owner_name']) ?> <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span></div>
            <div class="mkt-row"><span class="k">Claim ID</span><span class="v mono"><?= e($featuredListing['claim_id']) ?></span></div>
            <div class="mkt-row"><span class="k">Registered</span><span class="v mono"><?= e(date('d M Y', strtotime($featuredListing['created_at']))) ?></span></div>
            <div class="mkt-row"><span class="k">Category</span><span class="v"><?= e($featuredListing['category']) ?></span></div>
          </div>
          <div class="mkt-ctas">
            <?php if ($user): ?>
              <a class="btn btn-primary" href="start-conversation.php?item=<?= (int)$featuredListing['id'] ?>">Message Seller</a>
            <?php else: ?>
              <a class="btn btn-primary" href="login.php">Log in to message seller</a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="#registry">Browse Marketplace</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="notice" id="notice">
  <div class="wrap">
    <div class="notice-grid">
      <div class="reveal">
        <div class="kicker">If it's ever lost</div>
        <h2 class="sec-title">A stranger can reach you without ever knowing who you are.</h2>
        <p class="dropcap">Every registered item carries a Claim ID and a QR code. Scan it, and instead of a name, a phone number, or an address, a finder sees only a status and a way to send word — routed through YONZON, never through your inbox directly.</p>
        <div class="notice-index">
          <div class="idx-row"><span class="a">Personal details never shown</span><span class="b mono">§ 4.1</span></div>
          <div class="idx-row"><span class="a">Messages relayed through YONZON</span><span class="b mono">§ 4.2</span></div>
          <div class="idx-row"><span class="a">Status updates the moment it's reported lost</span><span class="b mono">§ 4.3</span></div>
        </div>
      </div>

      <div class="found-slip reveal">
        <div class="bar">SCANNED RECORD</div>
        <div class="found-inner">
          <div class="msg">This item has a verified owner.</div>
          <div class="found-row"><span class="k">Claim ID</span><span class="v mono">YZ-000184</span></div>
          <div class="found-row"><span class="k">Status</span><span class="status-lost">Lost</span></div>
          <div class="found-btn">Contact Owner</div>
          <div class="found-note">No phone number, email, or address is shared with the finder.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="directory" id="directory">
  <div class="wrap">
    <div class="reveal">
      <div class="kicker">Floor directory</div>
      <h2 class="sec-title">One record, five rooms to manage it in.</h2>
    </div>

    <div class="dir-list">
      <div class="dir-row">
        <div class="rn mono">01</div>
        <div class="name-wrap"><span class="name">Dashboard</span><span class="leader"></span></div>
        <div class="stat">Your registry at a glance</div>
        <div class="desc">An overview of everything you've filed, at a glance.</div>
      </div>
      <div class="dir-row">
        <div class="rn mono">02</div>
        <div class="name-wrap"><span class="name">My Claims</span><span class="leader"></span></div>
        <div class="stat">Full list</div>
        <div class="desc">All your registered possessions, in one place.</div>
      </div>
      <div class="dir-row">
        <div class="rn mono">03</div>
        <div class="name-wrap"><span class="name">Add Claim</span><span class="leader"></span></div>
        <div class="stat">1 step</div>
        <div class="desc">Name it, tag a category, attach a photo if you have one.</div>
      </div>
      <div class="dir-row">
        <div class="rn mono">04</div>
        <div class="name-wrap"><span class="name">Claim Details</span><span class="leader"></span></div>
        <div class="stat">Record + QR</div>
        <div class="desc">The complete ownership record and its scannable code.</div>
      </div>
      <div class="dir-row">
        <div class="rn mono">05</div>
        <div class="name-wrap"><span class="name">Report Lost</span><span class="leader"></span></div>
        <div class="stat">One click</div>
        <div class="desc">Changes public status and opens finder contact.</div>
      </div>
    </div>
  </div>
</section>

<section class="cta-sec">
  <div class="glow-field"></div>
  <div class="wrap reveal" style="position:relative;">
    <div class="cta-seal">
      <svg viewBox="0 0 120 120" fill="none" style="position:relative;">
        <circle cx="60" cy="60" r="56" stroke="url(#silverGrad)" stroke-width="1"/>
        <path d="M60 34L82 60L60 86L38 60L60 34Z" stroke="url(#silverGrad)" stroke-width="1"/>
        <path d="M48 48L72 72M72 48L48 72" stroke="url(#silverGrad)" stroke-width="1"/>
      </svg>
    </div>
    <h2>Give what you own a record that outlasts the receipt.</h2>
    <p class="sub">Five entries file your first claim. A lifetime to be glad you did.</p>
    <div class="hero-ctas center">
      <a class="btn btn-primary" href="<?= e($registerHref) ?>">Register an item</a>
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <div class="foot-top">
      <div class="foot-brand">
        <?php brand_mark(26); ?>
        <div class="tag">A verified ownership registry for the things worth keeping track of.</div>
      </div>
      <div class="foot-col">
        <div class="h">Registry</div>
        <a href="#certificate">Certificate</a>
        <a href="#ledger">The process</a>
        <a href="#notice">Lost &amp; found</a>
      </div>
      <div class="foot-col">
        <div class="h">Company</div>
        <a href="#">About</a>
        <a href="#">Contact</a>
        <a href="terms.php">Terms of Service</a>
        <a href="privacy.php">Privacy Policy</a>
      </div>
      <div class="foot-col">
        <div class="h">Filed by</div>
        <a href="#">Jayvee Yonzon</a>
        <a href="#">Framer &amp; Designer</a>
      </div>
    </div>
    <div class="foot-bottom">
      <span>&copy; <?= e($currentYear) ?> YONZON CLAIM REGISTRY — ALL RECORDS RESERVED</span>
      <span class="mono">REG. NO. 0001</span>
    </div>
  </div>
</footer>

<script src="<?= asset_url('js/main.js') ?>"></script>
</body>
</html>