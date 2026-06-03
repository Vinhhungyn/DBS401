<?php

require_once 'config.php';
require_once 'layout.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  die('403 Forbidden');
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
