<?php
// register.php (port 5000 - VULNERABLE, co y SQLi)
require_once 'config.php';
require_once 'layout.php';

$error   = '';
$success = '';
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $conn = get_conn();
        // LO HONG CO Y: noi chuoi thang, khong sanitize
        $sql = "INSERT INTO employees (username, email, password, role, salary)
                VALUES ('{$username}', '{$email}', '{$password}', 'user', 0)";
        if ($conn->query($sql)) {
            header('Location: /upload.php'); // neu da dang nhap thi vao thang
            exit;
        } else {
            $error = 'Lỗi: ' . $conn->error;
        }
        $conn->close();
    } catch (Exception $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

$err_html = $error   ? '<div class="alert-danger">'  . htmlspecialchars($error)   . '</div>' : '';
$suc_html = $success ? '<div class="alert-success">' . $success . '</div>' : '';
$uval     = htmlspecialchars($username);
$eval     = htmlspecialchars($email);

$content = <<<HTML
<div class="card" style="max-width:420px; margin:0 auto;">
  <h2>&#128221; Đăng ký tài khoản</h2>
  {$err_html}
  {$suc_html}
  <form method="POST" action="/register.php">
    <label>Tên đăng nhập</label>
    <input type="text" name="username" value="{$uval}" placeholder="Nhập username...">
    <label>Email</label>
    <input type="email" name="email" value="{$eval}" placeholder="Nhập email...">
    <label>Mật khẩu</label>
    <input type="password" name="password" placeholder="Nhập mật khẩu...">
    <button type="submit" style="width:100%;">Đăng ký</button>
  </form>
  <p class="hint">Đã có tài khoản? <a href="/login.php">Đăng nhập</a></p>
</div>
HTML;

render_layout($content);