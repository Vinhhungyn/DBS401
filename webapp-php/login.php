<?php
// ============================================================
// login.php — Trang đăng nhập
// Tương đương: @app.route("/login", methods=["GET","POST"])
// LỖ HỔNG CỐ Ý: nối chuỗi thẳng vào SQL, không dùng prepared statement
// LỖ HỔNG CỐ Ý: nối chuỗi thẳng vào SQL, không dùng prepared statement
// LỖ HỔNG CỐ Ý: nối chuỗi thẳng vào SQL, không dùng prepared statement
// LỖ HỔNG CỐ Ý: nối chuỗi thẳng vào SQL, không dùng prepared statement
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
        $stmt = $conn->prepare("SELECT id, username, role FROM employees WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_row()) {
            $_SESSION['user'] = $row[1];
            $_SESSION['role'] = $row[2];
            // LỖ HỔNG: set cookie role dựa trên role trong DB
            setcookie('role', $row[2], 0, '/');
            // Tao JWT token (vulnerable - secret key yeu)
            require_once 'jwt.php';
            $token = jwt_create($row[1], $row[2]);
            setcookie('token', $token, 0, '/');
            $stmt->close();
            $conn->close();
            header('Location: /search.php');
            exit;
        } else {
            $error = 'Sai tên đăng nhập hoặc mật khẩu!';
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}
// ---- Render HTML ----
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
