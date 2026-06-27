<?php
// logout.php — Xoa session + cookie logged_in
require_once 'config.php';

session_destroy();

// Xoa cookie logged_in
setcookie('logged_in', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

header('Location: /login.php');
exit;