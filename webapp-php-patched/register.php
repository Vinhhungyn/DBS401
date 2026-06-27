<?php
// register.php (port 5001 - PATCHED)
// An toan: prepared statement, KHONG hash (dong bo voi DB plaintext)
require_once 'config.php';
require_once 'layout.php';

$error   = '';
$success = '';
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (!$username || !$email || !$password) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu tối thiểu 6 ký tự!';
    } else {
        try {
            $conn = get_conn();

            // Kiem tra username ton tai
            $stmt = $conn->prepare("SELECT id FROM employees WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = 'Tên đăng nhập đã tồn tại!';
                $stmt->close();
            } else {
                $stmt->close();
                $role   = 'user';
                $salary = 0;

                // Prepared statement - luu plaintext (dong bo DB)
                $stmt2 = $conn->prepare(
                    "INSERT INTO employees (username, email, password, role, salary) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt2->bind_param("ssssi", $username, $email, $password, $role, $salary);
                if ($stmt2->execute()) {
                    header('Location: /upload.php'); // neu da dang nhap thi vao thang
                    exit;
                } else {
                    $error = 'Lỗi khi tạo tài khoản!';
                }
                $stmt2->close();
            }
            $conn->close();
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
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
    <input type="password" name="password" placeholder="Tối thiểu 6 ký tự...">
    <label>Xác nhận mật khẩu</label>
    <input type="password" name="confirm" placeholder="Nhập lại mật khẩu...">
    <button type="submit" style="width:100%;">Đăng ký</button>
  </form>
  <p class="hint">Đã có tài khoản? <a href="/login.php">Đăng nhập</a></p>
</div>
HTML;

render_layout($content);