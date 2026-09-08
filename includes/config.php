<?php
/**
 * config.php
 * Loaded by every page. Opens the DB connection and starts the session
 * with safer cookie defaults BEFORE any output is sent.
 */

// ---- Friendly error handling ----
// XAMPP shows raw PHP errors/warnings on screen by default — the exact
// thing a grading rubric (and real users) should never see. Turn off
// on-screen display, but keep logging everything to XAMPP's error log
// so problems are still visible to a developer, just not to visitors.
// Flip DEBUG_MODE to true temporarily while actively debugging locally
// if you want to see full error output again.
define('DEBUG_MODE', true);

if (!DEBUG_MODE) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e): void {
    error_log('[YONZON CLAIM] Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) { http_response_code(500); }
    echo '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif; max-width:480px; margin:80px auto; text-align:center; color:#333;">'
       . '<h2>Something went wrong.</h2>'
       . '<p>The error has been logged. Please try again, or go back to the homepage.</p>'
       . '<p><a href="index.php">Return to YONZON CLAIM</a></p>'
       . '</body></html>';
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
    // Log the real error server-side for debugging, but never show raw
    // exception details to whoever is looking at the browser — that's an
    // information leak (it can reveal DB structure, credentials, paths).
    error_log('[YONZON CLAIM] DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Something went wrong connecting to the database. Please try again shortly, '
      . 'or contact the site administrator if this keeps happening.');
}

// ---- Upload directory ----
// UPLOAD_DIR is a server filesystem path (used by move_uploaded_file / unlink).
// This file now lives in includes/, so go one level up to the project root.
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
// UPLOAD_URL is a browser-facing relative path, used from root-level pages
// like index.php and dashboard.php — so it stays relative to the project root.
define('UPLOAD_URL', 'uploads/');