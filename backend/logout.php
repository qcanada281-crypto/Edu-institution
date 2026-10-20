<?php
// backend/logout.php
require_once __DIR__.'/config.php';

// Start session (config may already start it, but ensure)
session_start();

// Unset all session variables
$_SESSION = [];

// Delete the session cookie if it exists
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Destroy the session
session_destroy();

// Return JSON response
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'message' => 'تم تسجيل الخروج بنجاح.'
]);
?>
