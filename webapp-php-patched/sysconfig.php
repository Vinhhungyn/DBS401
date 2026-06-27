<?php
// ============================================================
// sysconfig.php — Hiển thị cấu hình hệ thống
// Tương đương: @app.route("/config")
// ĐÃ FIX: yêu cầu đăng nhập + quyền admin mới được xem
// ============================================================
require_once 'config.php';
require_once 'layout.php';

// Chặn truy cập nếu chưa đăng nhập hoặc không phải admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>Bạn không có quyền truy cập trang này.</p>');
}

$content = '
<div class="card">
  <h2>&#9881; Thông tin cấu hình hệ thống</h2>
  <table>
    <tr><th>Tham số</th><th>Giá trị</th></tr>
    <tr><td>DB Host</td><td><code>' . DB_HOST . '</code></td></tr>
    <tr><td>DB Port</td><td><code>3307</code></td></tr>
    <tr><td>DB User</td><td><code>' . DB_USER . '</code></td></tr>
    <tr><td>DB Pass</td><td><code>' . DB_PASS . '</code></td></tr>
    <tr><td>App Version</td><td><code>1.0.0-dev</code></td></tr>
    <tr><td>Debug Mode</td><td><code>True</code></td></tr>
    <tr><td>Secret Key</td><td><code>supersecret123</code></td></tr>
  </table>
</div>';

render_layout($content);