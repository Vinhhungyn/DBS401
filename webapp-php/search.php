<?php
// ============================================================
// search.php — Tìm kiếm nhân viên
// Phân quyền:
//   user    → chỉ xem Username, Email (bình thường)
//             nhưng nếu SQLi inject thêm cột → lộ full (demo lỗ hổng)
//   manager → xem Username, Email, Salary
//   admin   → xem full (ID, Username, Role, Email, Salary)
// LỖ HỔNG CỐ Ý: UNION-based SQLi
// ============================================================
require_once 'config.php';
require_once 'layout.php';

$role = 'guest';
if (isset($_COOKIE['token'])) {
    require_once 'jwt.php';
    $payload = jwt_decode($_COOKIE['token']);
    if ($payload && isset($payload['role'])) {
        $role = $payload['role'];
    }
}

if (!in_array($role, ['admin', 'manager', 'user'], true)) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>Bạn không có quyền truy cập trang này.</p>');
}

$q         = $_GET['q'] ?? '';
$results   = null;
$num_cols  = 0;
$col_names = [];

if ($q !== '') {
    try {
        $conn = get_conn();

        // LỖ HỔNG CỐ Ý: nối chuỗi thẳng vào SQL, không escape
        $sql = "SELECT id, username, role, email, salary FROM employees WHERE username='{$q}'";
        $res = $conn->query($sql);

        $results = [];
        if ($res) {
            // Lấy tên cột thực tế từ result (để render đúng dù SQLi đổi cột)
            $fields = $res->fetch_fields();
            foreach ($fields as $f) {
                $col_names[] = strtoupper($f->name);
            }
            $num_cols = count($col_names);

            while ($row = $res->fetch_row()) {
                $results[] = $row;
            }
        }
        $conn->close();
    } catch (Exception $e) {
        $results  = [];
        $num_cols = 0;
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
        // Với role user: nếu SQLi inject làm số cột != 5 hoặc
        // giá trị lạ xuất hiện → render toàn bộ cột (lộ dữ liệu — demo lỗ hổng)
        // Bình thường (đúng 5 cột, search thường) → chỉ hiện Username + Email
        $is_sqli_result = ($role === 'user') && ($num_cols != 5 || count($results) > 1);

        if ($role === 'admin' || $is_sqli_result) {
            // Render full: header động theo tên cột thực tế
            $header = implode('', array_map(fn($c) => "<th>{$c}</th>", $col_names));
            $content .= "<table><tr>{$header}</tr>";
            foreach ($results as $r) {
                $content .= '<tr>';
                foreach ($r as $cell) {
                    $val = htmlspecialchars($cell ?? '');
                    $content .= "<td>{$val}</td>";
                }
                $content .= '</tr>';
            }
            $content .= '</table>';

            if ($is_sqli_result) {
                $content .= '<p style="color:#e53e3e;font-size:13px;margin-top:8px;">
                  &#9888; Dữ liệu bị lộ do SQL Injection!</p>';
            }

        } elseif ($role === 'manager') {
            $content .= '<table><tr><th>Username</th><th>Email</th><th>Salary</th></tr>';
            foreach ($results as $r) {
                $uname  = htmlspecialchars($r[1] ?? '');
                $email  = htmlspecialchars($r[3] ?? '');
                $salary = is_numeric($r[4])
                        ? number_format((float)$r[4], 0, '.', ',') . ' đ'
                        : htmlspecialchars($r[4] ?? '');
                $content .= "<tr>
                  <td><b>{$uname}</b></td>
                  <td>{$email}</td>
                  <td>{$salary}</td>
                </tr>";
            }
            $content .= '</table>';

        } else {
            // user bình thường: chỉ Username + Email
            $content .= '<table><tr><th>Username</th><th>Email</th></tr>';
            foreach ($results as $r) {
                $uname = htmlspecialchars($r[1] ?? '');
                $email = htmlspecialchars($r[3] ?? '');
                $content .= "<tr>
                  <td><b>{$uname}</b></td>
                  <td>{$email}</td>
                </tr>";
            }
            $content .= '</table>';
        }

    } else {
        $content .= '<p style="color:#999;">Không tìm thấy nhân viên nào.</p>';
    }
    $content .= '</div>';
}

render_layout($content);
