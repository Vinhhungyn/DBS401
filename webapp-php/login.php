<?php
// ============================================================
// login.php — Trang đăng nhập
// LỖ HỔNG CỐ Ý: nối chuỗi thẳng vào SQL, không dùng prepared statement
// ============================================================
require_once 'config.php';
require_once 'layout.php';

$error    = '';
$success  = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $conn = get_conn();

        // LỖ HỔNG CỐ Ý: nối chuỗi thẳng, không escape -> SQL Injection
        $sql    = "SELECT id, username, role FROM employees WHERE username='{$username}' AND password='{$password}'";
        $result = $conn->query($sql);

        if ($result && $row = $result->fetch_row()) {
            $_SESSION['user'] = $row[1];
            $_SESSION['role'] = $row[2];
        
            require_once 'jwt.php';
            $token = jwt_create($row[1], $row[2]);
            setcookie('token', $token, 0, '/');
            $conn->close();

            if (in_array($row[2], ['admin', 'manager'], true)) {
                header('Location: /search.php');
            } else {
                header('Location: /upload.php');
            }
            exit;
        } else {
            $error = 'Sai tên đăng nhập hoặc mật khẩu!';
            $conn->close();
        }
    } catch (Exception $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

$err_html = $error   ? '<div class="alert-danger">'  . htmlspecialchars($error)   . '</div>' : '';
$suc_html = $success ? '<div class="alert-success">' . htmlspecialchars($success) . '</div>' : '';
$uval     = htmlspecialchars($username);

$content = <<<HTML
<div class="card" style="max-width:420px; margin:0 auto;">
  <h2>&#128100; Đăng nhập</h2>
  {$err_html}
  {$suc_html}
  <form method="POST" action="/login.php">
    <label>Tên đăng nhập</label>
    <input type="text" name="username" placeholder="Nhập username..." value="{$uval}">
    <label>Mật khẩu</label>
    <input type="password" name="password" placeholder="Nhập mật khẩu...">
    <button type="submit" style="width:100%;">Đăng nhập</button>
  </form>
</div>
HTML;

render_layout($content);