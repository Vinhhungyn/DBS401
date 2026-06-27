<?php
// ============================================================
// search.php — Tìm kiếm nhân viên
// Tương đương: @app.route("/search")
// LỖ HỔNG CỐ Ý: UNION-based SQLi, hiện query để demo
// FIX: chi admin va manager moi duoc xem trang nay
// ============================================================
require_once 'config.php';
require_once 'layout.php';
$role = $_COOKIE['role'] ?? ($_SESSION['role'] ?? 'guest');
if (!in_array($role, ['admin', 'manager'], true)) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>Bạn không có quyền truy cập trang này.</p>');
}

$q         = $_GET['q'] ?? '';
$results   = null;   // null = chưa search
$sql_shown = null;

if ($q !== '') {
    try {
        $conn = get_conn();

        // LỖ HỔNG CỐ Ý: nối chuỗi thẳng vào SQL, không escape
        $sql       = "SELECT id, username, role, email, salary FROM employees WHERE username='{$q}'";
        $sql_shown = $sql;
        $res       = $conn->query($sql);

        $results = [];
        if ($res) {
            while ($row = $res->fetch_row()) {
                $results[] = $row;
            }
        }
        $conn->close();
    } catch (Exception $e) {
        $results   = [];
        $sql_shown = 'LỖI: ' . $e->getMessage();
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

// ---- Debug SQL ----
// if ($sql_shown !== null) {
//    $sql_esc  = htmlspecialchars($sql_shown);
//    $content .= <<<HTML
//  <div class="card" style="background:#fff8e1;">
//  <h2>&#128196; Query đã thực thi (debug mode)</h2>
//  <code style="background:#f5f5f5; padding:10px; display:block; border-radius:4px; word-break:break-all;">{$sql_esc}</code>
//</div>
//HTML;
//}

render_layout($content);