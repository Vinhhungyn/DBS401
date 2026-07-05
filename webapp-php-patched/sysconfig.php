<?php
// ============================================================
// sysconfig.php (port 5001 - PATCHED)
// FIX: yeu cau dang nhap + quyen admin
// FIX: KHONG hien password, secret key - chi hien thong tin an toan
// FIX: check role tu JWT, khong chi dung $_SESSION['role']
// ============================================================
require_once 'config.php';
require_once 'layout.php';
require_once 'jwt.php';

// FIX: phai dang nhap
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

// FIX: check role tu JWT (verify signature)
$role = 'guest';
if (isset($_COOKIE['token'])) {
    $payload = jwt_decode($_COOKIE['token']);
    if ($payload && isset($payload['role'])) {
        $role = $payload['role'];
    }
}

// FIX: double check voi SESSION role
$session_role = $_SESSION['role'] ?? 'user';
if ($role !== 'admin' || $session_role !== 'admin') {
    header('Location: /upload.php');
    exit;
}

// FIX: chi hien thong tin an toan, khong lo password/secret
$content = '
<div class="card">
  <h2>&#9881; Thong tin cau hinh he thong</h2>
  <table>
    <tr><th>Tham so</th><th>Gia tri</th></tr>
    <tr><td>DB Host</td><td><code>' . htmlspecialchars(DB_HOST) . '</code></td></tr>
    <tr><td>DB Port</td><td><code>' . DB_PORT . '</code></td></tr>
    <tr><td>DB User</td><td><code>' . htmlspecialchars(DB_USER) . '</code></td></tr>
    <tr><td>DB Pass</td><td><code>********</code></td></tr>
    <tr><td>App Version</td><td><code>1.0.0</code></td></tr>
    <tr><td>Debug Mode</td><td><code>False</code></td></tr>
    <tr><td>Secret Key</td><td><code>********</code></td></tr>
  </table>
  <p class="hint" style="margin-top:12px;">Thong tin nhay cam duoc an de bao mat.</p>
</div>';

render_layout($content);
