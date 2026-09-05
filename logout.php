<?php
require_once __DIR__ . '/includes/config.php';

$_SESSION = [];

// Explicitly clear the session cookie, not just server-side data.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

if (headers_sent($file, $line)) {
    // Something (likely stray whitespace or output before an opening <?php
    // tag in an included file) printed content before this point, so a
    // redirect header can't be sent. Fall back to a meta-refresh so
    // logout still works instead of silently failing.
    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=index.php">'
       . '</head><body>Logging out... <a href="index.php">Click here if you are not redirected</a>.'
       . "<!-- headers already sent from $file:$line -->"
       . '</body></html>';
    exit;
}

header('Location: index.php');
exit;
