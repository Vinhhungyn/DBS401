<?php
// ============================================================
// search.php (port 5001 - PATCHED) — Tìm kiếm nhân viên
// FIX 1: chi admin va manager moi duoc xem trang nay
// FIX 2: dung prepared statement, khong con SQLi
// ============================================================
require_once 'config.php';
require_once 'layout.php';

// FIX: kiem tra quyen truy cap o phia server
// FIX: doc role tu JWT token (da verify signature trong jwt.php)
$role = 'guest';
if (isset($_COOKIE['token'])) {
    require_once 'jwt.php';
    $payload = jwt_decode($_COOKIE['token']);
    if ($payload && isset($payload['role'])) {
        $role = $payload['role'];
    }
}
if (!in_array($role, ['admin', 'manager'], true)) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>Bạn không có quyền truy cập trang này.</p>');
}

$q       = $_GET['q'] ?? '';
$results = null; // null = chưa search

if ($q !== '') {
    try {
        $conn = get_conn();

        // FIX: prepared statement - khong con SQLi
        $stmt = $conn->prepare("SELECT id, username, role, email, salary FROM employees WHERE username = ?");
        $stmt->bind_param("s", $q);
        $stmt->execute();
        $res = $stmt->get_result();

        $results = [];
        if ($res) {
            while ($row = $res->fetch_row()) {
                $results[] = $row;
            }
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $results = [];
    }
}

// ---- Search form ----
$qval    = htmlspecialchars($q);
$content = <<<HTML
<div class="card">
  <h2>&#128269; Tìm kiếm nhân viên</h2>
  <form method="GET" action="/search.php">
    <div style="display:flex; gap:12px;">
      <input type="text" name="q" placeholder="Nhập tên nhân viên..." value="{$qval}" style="margin:0;">
      <button type="submit" style="white-space:nowrap;">Tìm kiếm</button>
    </div>
  </form>
</div>
HTML;

// ---- Results ----
if ($results !== null) {
    $count    = count($results);
    $content .= "<div class='card'><h2>Kết quả ({$count} bản ghi)</h2>";

    if ($count > 0) {
        $content .= '<table><tr><th>ID</th><th>Username</th><th>Role</th><th>Email</th><th>Salary</th></tr>';
        foreach ($results as $r) {
            $id     = htmlspecialchars($r[0] ?? '');
            $uname  = htmlspecialchars($r[1] ?? '');
            $role_r = htmlspecialchars($r[2] ?? '');
            $email  = htmlspecialchars($r[3] ?? '');
            $salary = is_numeric($r[4])
                    ? number_format((float)$r[4], 0, '.', ',') . ' đ'
                    : htmlspecialchars($r[4] ?? '');
            $content .= "<tr>
              <td>{$id}</td>
              <td><b>{$uname}</b></td>
              <td><span class='badge badge-{$role_r}'>{$role_r}</span></td>
              <td>{$email}</td>
              <td>{$salary}</td>
            </tr>";
        }
        $content .= '</table>';
    } else {
        $content .= '<p style="color:#999;">Không tìm thấy nhân viên nào.</p>';
    }
    $content .= '</div>';
}

render_layout($content);