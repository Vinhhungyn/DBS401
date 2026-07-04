<?php
// ============================================================
// error403.php - Trang trung gian xu ly WAF block
// FIX: thay vi hien 403 Forbidden, redirect ve trang goc
// kem ?waf_block=1 de PHP hien thong bao loi tu nhien
// ============================================================

// Doc referer de biet dang o trang nao
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Map referer -> trang redirect
$allowed_pages = [
    '/login.php',
    '/upload.php',
    '/feedback.php',
    '/search.php',
    '/register.php',
];

$redirect_to = '/login.php'; // default fallback

if ($referer) {
    $path = parse_url($referer, PHP_URL_PATH);
    if (in_array($path, $allowed_pages, true)) {
        $redirect_to = $path;
    }
}

// Redirect ve trang goc kem waf_block=1
header('Location: ' . $redirect_to . '?waf_block=1');
exit;
