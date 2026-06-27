<?php
// logout.php — Xoa session + TAT CA cookie lien quan dang nhap
require_once 'config.php';

// Xoa session data
$_SESSION = [];

// Xoa cookie PHPSESSID (session cookie) truoc khi destroy
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

// Xoa cookie logged_in
setcookie('logged_in', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Xoa cookie token (JWT) - TRUOC DAY BI THIEU, la ly do token con sau logout
setcookie('token', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Xoa cookie role - TRUOC DAY BI THIEU
setcookie('role', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

header('Location: /login.php');
exit;