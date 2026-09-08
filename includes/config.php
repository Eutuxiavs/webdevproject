<?php
/**
 * config.php
 * Loaded by every page. Opens the DB connection and starts the session
 * with safer cookie defaults BEFORE any output is sent.
 */

// ---- Global error handling: log real errors, show users a friendly message ----
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../error.log');
error_reporting(E_ALL);

function yz_friendly_error_page(): void {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Something went wrong</title></head>'
       . '<body style="font-family:sans-serif; text-align:center; padding:80px 20px; background:#111; color:#eee;">'
       . '<h1>Something went wrong.</h1><p>Please try again, or head back to the <a href="dashboard.php" style="color:#8ab4f8;">dashboard</a>.</p>'
       . '</body></html>';
}

set_exception_handler(function (Throwable $e) {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    yz_friendly_error_page();
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false; // respects @ suppression
    error_log("PHP Warning/Notice: $message in $file:$line");
    return true; // don't let it fall through to default display
});

// ---- Session hardening (must run before session_start) ----
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,   // JS can't read the session cookie
    'samesite' => 'Lax',
]);
session_start();

// ---- Database credentials ----
// Defaults match a fresh XAMPP install (user "root", no password).
define('DB_HOST', 'localhost');
define('DB_NAME', 'webdevproject');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Please make sure the database server is running and try again.');
}

// ---- Upload directory ----
// UPLOAD_DIR is a server filesystem path (used by move_uploaded_file / unlink).
// This file now lives in includes/, so go one level up to the project root.
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
// UPLOAD_URL is a browser-facing relative path, used from root-level pages
// like index.php and dashboard.php — so it stays relative to the project root.
define('UPLOAD_URL', 'uploads/');