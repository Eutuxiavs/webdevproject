<?php
/**
 * config.php
 * Loaded by every page. Opens the DB connection and starts the session
 * with safer cookie defaults BEFORE any output is sent.
 */

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
    http_response_code(500);
    die('Database connection failed. Make sure MySQL is running in XAMPP '
      . 'and that database.sql has been imported. Details: ' . $e->getMessage());
}

// ---- Upload directory ----
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
