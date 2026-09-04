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

/* ---------------- Claims / items ---------------- */
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
        'sold'     => 'Sold',
    ];
    return $map[$status] ?? ucfirst($status);
}

function status_class(string $status): string {
    switch ($status) {
        case 'lost':     return 'lost';
        case 'for_sale': return 'sale';
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

    return $filename;
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
