<?php
// register.php (port 5001 - PATCHED)
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

    if (!$username || !$password) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu tối thiểu 6 ký tự!';
    } else {
        try {
            $conn = get_conn();
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

                // FIX: hash password bằng bcrypt trước khi lưu vào DB
                // Vulnerable (5000): lưu $password plaintext trực tiếp — không an toàn
                // Patched  (5001): password_hash() tạo bcrypt hash ($2y$10$...)
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmt2 = $conn->prepare(
                    "INSERT INTO employees (username, email, password, role, salary) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt2->bind_param("ssssi", $username, $email, $hashedPassword, $role, $salary);
                if ($stmt2->execute()) {
                    $success = 'Đăng ký thành công! <a href="/login.php">Đăng nhập ngay</a>';
                } else {
                    $error = 'Lỗi khi tạo tài khoản!';
                }
                $stmt2->close();
            }
            $conn->close();
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống.';
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
    <input type="text" name="username" value="{$uval}" placeholder="Tối thiểu 3 ký tự, chỉ a-z, 0-9, _">
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
