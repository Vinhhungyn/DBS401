<?php
// ============================================================
// search.php (port 5001 - PATCHED)
// FIX 1: chua dang nhap -> redirect login, khong phai die 403
// FIX 2: chi admin va manager moi duoc xem
// FIX 3: prepared statement, khong SQLi
// FIX 4: WAF block -> hien "Khong tim thay" thay vi 403
// ============================================================
require_once 'config.php';
require_once 'layout.php';

// FIX: chua dang nhap thi redirect ve login
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

// FIX: da dang nhap nhung khong du quyen thi redirect ve upload
$role = 'guest';
if (isset($_COOKIE['token'])) {
    require_once 'jwt.php';
    $payload = jwt_decode($_COOKIE['token']);
    if ($payload && isset($payload['role'])) {
        $role = $payload['role'];
    }
}
if (!in_array($role, ['admin', 'manager'], true)) {
    header('Location: /upload.php');
    exit;
}

$q       = $_GET['q'] ?? '';
$results = null;

// FIX: WAF block -> hien thong bao "khong tim thay" thay vi 403
$waf_msg = '';
if (isset($_GET['waf_block'])) {
    $waf_msg = "<div class='alert-danger'>Khong tim thay nhan vien phu hop voi tu khoa nay.</div>";
    $results = [];
}

if ($q !== '' && !isset($_GET['waf_block'])) {
    try {
        $conn = get_conn();
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

$qval    = htmlspecialchars($q);
$content = <<<HTML
<div class="card">
  <h2>&#128269; Tim kiem nhan vien</h2>
  {$waf_msg}
  <form method="GET" action="/search.php">
    <div style="display:flex; gap:12px;">
      <input type="text" name="q" placeholder="Nhap ten nhan vien..." value="{$qval}" style="margin:0;">
      <button type="submit" style="white-space:nowrap;">Tim kiem</button>
    </div>
  </form>
</div>
HTML;

if ($results !== null && !isset($_GET['waf_block'])) {
    $count    = count($results);
    $content .= "<div class='card'><h2>Ket qua ({$count} ban ghi)</h2>";

    if ($count > 0) {
        $content .= '<table><tr><th>ID</th><th>Username</th><th>Role</th><th>Email</th><th>Salary</th></tr>';
        foreach ($results as $r) {
            $id     = htmlspecialchars($r[0] ?? '');
            $uname  = htmlspecialchars($r[1] ?? '');
            $role_r = htmlspecialchars($r[2] ?? '');
            $email  = htmlspecialchars($r[3] ?? '');
            $salary = is_numeric($r[4])
                    ? number_format((float)$r[4], 0, '.', ',') . ' d'
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
        $content .= '<p style="color:#999;">Khong tim thay nhan vien nao.</p>';
    }
    $content .= '</div>';
}

render_layout($content);
