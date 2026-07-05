<?php
// ============================================================
// logout.php (port 5001 - PATCHED)
// FIX: xoa sach session + tat ca cookie
// ============================================================
require_once 'config.php';

// FIX: Xoa het bien session
$_SESSION = [];

// FIX: Xoa session cookie
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $p['path'], $p['domain'],
        $p['secure'], $p['httponly']
    );
}
session_destroy();

// FIX: Xoa cookie token va logged_in
$expired = [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
];
setcookie('token',     '', $expired);
setcookie('logged_in', '', $expired);

header('Location: /login.php');
exit;
