<?php
/**
 * functions.php
 * Shared helpers. Include config.php before this file.
 */

/* ---------------- Output escaping ---------------- */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/* ---------------- Auth ---------------- */
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_user(): ?array {
    global $pdo;
    if (!current_user_id()) return null;
    static $cached = null;
    if ($cached === null) {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
        $stmt->execute([current_user_id()]);
        $cached = $stmt->fetch() ?: null;
    }
    return $cached;
}

function require_login(): void {
    if (!current_user_id()) {
        header('Location: login.php');
        exit;
    }
}

/* ---------------- CSRF ---------------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        die('Security check failed. Go back and try again.');
    }
}

/* ---------------- Brand mark (logo) ---------------- */
/**
 * Renders the site logo. This is the ONLY place the logo markup lives —
 * every page calls brand_mark() instead of pasting the SVG inline, so
 * swapping the logo means editing ONE function, not every page.
 *
 * TO USE YOUR OWN LOGO IMAGE:
 * 1. Save your logo file as assets/img/logo.png (any image works —
 *    png, jpg, or svg — just keep the filename "logo" or update the
 *    path below to match).
 * 2. Replace the <svg>...</svg> block below with:
 *      echo '<img src="' . asset_path('img/logo.png') . '" alt="YONZON"
 *            style="width:' . $size . 'px; height:' . $size . 'px; object-fit:contain;">';
 * That's it — every nav bar, footer, and auth page updates at once,
 * and object-fit:contain keeps it a fixed size no matter what
 * dimensions your source image actually is.
 */
function brand_mark(int $size = 34): void {
    echo '<img src="' . asset_path('img/logo.png') . '" alt="YONZON" '
       . 'style="width:' . $size . 'px; height:' . $size . 'px; object-fit:contain; display:block;">';
}

/**
 * Builds a path to a file in assets/, relative to whichever page calls it.
 * Every page in this project lives at the project root, so this is just
 * "assets/..." — kept as a function so it's a single place to change if
 * that ever stops being true.
 */
function asset_path(string $relative): string {
    return 'assets/' . ltrim($relative, '/');
}

/**
 * Same as asset_path(), but appends ?v=<file modified time> automatically.
 * This means every time you save a new version of style.css or main.js,
 * the URL changes and the browser is FORCED to fetch the new file instead
 * of serving a stale cached copy. Use this for CSS/JS; use asset_path()
 * for things like the logo where cache-busting doesn't matter.
 */
function asset_url(string $relative): string {
    $path = asset_path($relative);
    $absolute = dirname(__DIR__) . '/' . $path; // functions.php lives in includes/, so climb up one
    $version = @filemtime($absolute);
    return $path . ($version ? ('?v=' . $version) : '');
}

/* ---------------- Categories (shared by add-item + browse filters) ---------------- */
function yz_categories(): array {
    return ['Photography', 'Timepieces', 'Electronics', 'Cycling', 'Instruments', 'Footwear', 'Other'];
}

/* ---------------- Offers: expiry + notifications + ratings ---------------- */

/** Auto-expires pending offers older than 7 days. Call once near the top of any page that shows offers. */
function auto_expire_offers(PDO $pdo): void {
    $pdo->exec(
        'UPDATE offers SET status = "expired"
         WHERE status = "pending" AND expires_at IS NOT NULL AND expires_at < NOW()'
    );
}

/** Renders a small red badge with the count of pending offers this user needs to act on (as seller). */
function pending_offer_badge(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS n FROM offers WHERE seller_id = ? AND status = "pending"');
    $stmt->execute([$userId]);
    $n = (int)$stmt->fetch()['n'];
    if ($n === 0) return '';
    $label = $n > 99 ? '99+' : (string)$n;
    return '<span class="nav-badge">' . e($label) . '</span>';
}

/** Renders a compact star display for a 1-5 rating (rounded to nearest whole star). */
function star_display(float $rating): string {
    $rounded = (int)round($rating);
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rounded ? '&#9733;' : '&#9734;'; // filled / empty star
    }
    return $out;
}


function category_icon_key(string $category): string {
    $map = [
        'Photography' => 'camera',
        'Timepieces'  => 'watch',
        'Electronics' => 'laptop',
        'Cycling'     => 'bike',
        'Instruments' => 'guitar',
        'Footwear'    => 'sneaker',
    ];
    return $map[$category] ?? 'box';
}

function yz_icon(string $key): string {
    $icons = [
        'camera'  => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7l1.5-2.5h5L16 7"/><circle cx="12" cy="13.5" r="3.6"/></svg>',
        'watch'   => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="6.5"/><path d="M12 8.7V12l2.4 1.4"/><path d="M9 2.5h6M9 21.5h6"/></svg>',
        'laptop'  => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4.5" width="16" height="10.5" rx="1"/><path d="M2 19.5h20l-2-3.5H4z"/></svg>',
        'bike'    => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="17" r="3.4"/><circle cx="18" cy="17" r="3.4"/><path d="M6 17l4-9h4l4 9M10 8h4M9 17h9"/></svg>',
        'guitar'  => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2v9"/><circle cx="9" cy="15.5" r="4"/><circle cx="9" cy="15.5" r="1.2"/><path d="M11.5 12.5c2-.3 3.3.6 3.3 2.3 0 1.6-1.2 2.6-3 2.4"/></svg>',
        'sneaker' => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18h19c1 0 1.6-1 1-1.8l-3-4c-.6-.8-1.6-1.2-2.6-1.2H10c-1 0-1.6-.4-2.2-1.1L6 7.5C5 6.4 3.4 6.6 2.7 7.8L2 9v9z"/><path d="M2 15h5M9 15v2M13 15v2"/></svg>',
        'box'     => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>',
    ];
    return $icons[$key] ?? $icons['box'];
}

function status_label(string $status): string {
    $map = [
        'owned'    => 'Owned',
        'warranty' => 'Warranty',
        'lost'     => 'Lost',
        'for_sale' => 'For Sale',
        'reserved' => 'Offer Pending',
        'sold'     => 'Sold',
    ];
    return $map[$status] ?? ucfirst($status);
}

function status_class(string $status): string {
    switch ($status) {
        case 'lost':     return 'lost';
        case 'for_sale': return 'sale';
        case 'reserved': return 'reserved';
        case 'sold':     return 'sold';
        default:         return '';
    }
}

/** Generates a claim ID like YZ-CAME-000042 from a category + numeric id. */
function generate_claim_id(string $category, int $id): string {
    $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category) . 'XXXX', 0, 4));
    return 'YZ-' . $prefix . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}

/* ---------------- File uploads ---------------- */
/**
 * Validates and moves an uploaded image. Returns the stored filename
 * (relative to UPLOAD_DIR) on success, or null if no file was sent.
 * Throws a RuntimeException on invalid/unsafe files.
 */
function handle_photo_upload(string $inputName): ?string {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$inputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try a different file.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image must be smaller than 5MB.');
    }

    // Check the real content, not just the filename extension.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, or WEBP images are allowed.');
    }

    $ext      = $allowed[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext; // random name, no path traversal
    $dest     = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }

    resize_image_if_needed($dest, $mime, 1200);

    return $filename;
}

/**
 * Shrinks an image down to $maxWidth (preserving aspect ratio) if it's
 * wider than that, so a 5MB phone photo doesn't sit on disk untouched.
 * Silently does nothing if the GD extension isn't available — resizing
 * is a nice-to-have, not something that should break uploads if it's
 * missing.
 */
function resize_image_if_needed(string $path, string $mime, int $maxWidth): void {
    if (!extension_loaded('gd')) return;

    [$width, $height] = getimagesize($path) ?: [0, 0];
    if (!$width || $width <= $maxWidth) return; // already small enough

    $source = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png'  => imagecreatefrompng($path),
        'image/webp' => imagecreatefromwebp($path),
        default       => null,
    };
    if (!$source) return;

    $newHeight = (int)round($height * ($maxWidth / $width));
    $resized = imagecreatetruecolor($maxWidth, $newHeight);

    if ($mime === 'image/png') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }

    imagecopyresampled($resized, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);

    match ($mime) {
        'image/jpeg' => imagejpeg($resized, $path, 85),
        'image/png'  => imagepng($resized, $path, 6),
        'image/webp' => imagewebp($resized, $path, 85),
        default       => null,
    };

    imagedestroy($source);
    imagedestroy($resized);
}

/* ---------------- Flash messages ---------------- */
function flash_set(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}
function flash_get(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/* ---------------- Login rate limiting ---------------- */
const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 10;

/** Returns true if this account is currently locked out from too many failed attempts. */
function is_account_locked(PDO $pdo, int $userId): bool {
    $stmt = $pdo->prepare('SELECT locked_until FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row && $row['locked_until'] && strtotime($row['locked_until']) > time();
}

/** Records a failed login attempt, locking the account temporarily after too many. */
function record_failed_login(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('UPDATE users SET failed_login_count = failed_login_count + 1 WHERE id = ?');
    $stmt->execute([$userId]);

    $stmt = $pdo->prepare('SELECT failed_login_count FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $count = (int)$stmt->fetch()['failed_login_count'];

    if ($count >= MAX_LOGIN_ATTEMPTS) {
        $stmt = $pdo->prepare('UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL ' . LOCKOUT_MINUTES . ' MINUTE) WHERE id = ?');
        $stmt->execute([$userId]);
    }
}

/** Clears the failed-attempt counter after a successful login. */
function reset_login_attempts(PDO $pdo, int $userId): void {
    $pdo->prepare('UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = ?')->execute([$userId]);
}

/* ---------------- Blocking ---------------- */

/** True if either user has blocked the other (blocking is treated as mutual). */
function users_blocked(PDO $pdo, int $userA, int $userB): bool {
    $stmt = $pdo->prepare(
        'SELECT 1 FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?) LIMIT 1'
    );
    $stmt->execute([$userA, $userB, $userB, $userA]);
    return (bool)$stmt->fetch();
}

/* ---------------- Verified badge ---------------- */

/** A lightweight "verified" signal: has this user completed at least one deal? */
function is_verified_trader(PDO $pdo, int $userId): bool {
    $stmt = $pdo->prepare(
        'SELECT 1 FROM offers WHERE (buyer_id = ? OR seller_id = ?) AND status = "completed" LIMIT 1'
    );
    $stmt->execute([$userId, $userId]);
    return (bool)$stmt->fetch();
}

/* ---------------- Recently viewed (session-based, no DB needed) ---------------- */

/** Adds an item id to the front of the recently-viewed list, capped at 8 entries. */
function track_recently_viewed(int $itemId): void {
    $list = $_SESSION['recently_viewed'] ?? [];
    $list = array_values(array_diff($list, [$itemId])); // remove if already present
    array_unshift($list, $itemId);
    $_SESSION['recently_viewed'] = array_slice($list, 0, 8);
}

function get_recently_viewed_ids(): array {
    return $_SESSION['recently_viewed'] ?? [];
}

/* ---------------- Condition / misc labels ---------------- */
function condition_label(?string $condition): string {
    $map = ['new' => 'New', 'like_new' => 'Like New', 'good' => 'Good', 'fair' => 'Fair'];
    return $condition ? ($map[$condition] ?? ucfirst($condition)) : '';
}

function yz_conditions(): array {
    return ['new' => 'New', 'like_new' => 'Like New', 'good' => 'Good', 'fair' => 'Fair'];
}

/** Defense-in-depth text cleanup for free-text fields (trims + strips control characters).
 *  This is NOT what stops SQL injection — prepared statements do that. This just keeps
 *  stray control characters out of things like names and messages. */
function clean_text(string $value): string {
    $value = trim($value);
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
}