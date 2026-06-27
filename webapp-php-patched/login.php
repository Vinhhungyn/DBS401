<?php
// login.php (port 5001 - PATCHED)
require_once 'config.php';
require_once 'layout.php';

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $conn = get_conn();
        $stmt = $conn->prepare("SELECT id, username, role, password FROM employees WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            if ($password === $row['password']) {
                $_SESSION['user'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                require_once 'jwt.php';
                $token = jwt_create($row['username'], $row['role']);

                // Cookie token - httponly, samesite Strict
                setcookie('token', $token, [
                    'expires'  => time() + 3600,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);

                // Cookie logged_in
                setcookie('logged_in', '1', [
                    'expires'  => time() + 3600,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);

                $stmt->close();
                $conn->close();

                if (in_array($row['role'], ['admin', 'manager'], true)) {
                    header('Location: /search.php');
                } else {
                    header('Location: /upload.php');
                }
                exit;
            }
        }
        $error = 'Sai tên đăng nhập hoặc mật khẩu!';
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $error = 'Lỗi hệ thống.';
    }
}

$err_html = $error ? '<div class="alert-danger">' . htmlspecialchars($error) . '</div>' : '';
$uval     = htmlspecialchars($username);

$content = <<<HTML
<div class="card" style="max-width:420px; margin:0 auto;">
  <h2>&#128100; Đăng nhập</h2>
  {$err_html}
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