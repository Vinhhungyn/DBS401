<?php
// ============================================================
// register.php (port 5001 - PATCHED)
// FIX: bcrypt hash, prepared statement, CSRF, validate input
// ============================================================
require_once 'config.php';
require_once 'layout.php';

if (isset($_SESSION['user'])) {
    header('Location: /upload.php');
    exit;
}

$error    = '';
$success  = '';
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIX: CSRF check
    if (!csrf_verify()) {
        $error = 'Phien lam viec het han, vui long thu lai!';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm']       ?? '';

        // FIX: validate input day du
        if (!$username || !$password) {
            $error = 'Vui long dien day du thong tin!';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $error = 'Username chi chua a-z, 0-9, _ va 3-50 ky tu!';
        } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email khong hop le!';
        } elseif (strlen($password) < 8) {
            $error = 'Mat khau toi thieu 8 ky tu!';
        } elseif ($password !== $confirm) {
            $error = 'Mat khau xac nhan khong khop!';
        } else {
            try {
                $conn = get_conn();
                $stmt = $conn->prepare("SELECT id FROM employees WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error = 'Ten dang nhap da ton tai!';
                    $stmt->close();
                } else {
                    $stmt->close();
                    $role   = 'user';
                    $salary = 0;

                    // FIX: hash password bang bcrypt truoc khi luu
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    $stmt2 = $conn->prepare(
                        "INSERT INTO employees (username, email, password, role, salary) VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt2->bind_param("ssssi", $username, $email, $hashedPassword, $role, $salary);
                    if ($stmt2->execute()) {
                        $success = 'Dang ky thanh cong! <a href="/login.php">Dang nhap ngay</a>';
                        $username = $email = '';
                    } else {
                        $error = 'Loi khi tao tai khoan!';
                    }
                    $stmt2->close();
                }
                $conn->close();
            } catch (Exception $e) {
                error_log('Register error: ' . $e->getMessage());
                $error = 'Loi he thong. Vui long thu lai!';
            }
        }
    }
}

$csrf     = csrf_token();
$err_html = $error   ? '<div class="alert-danger">'  . htmlspecialchars($error)   . '</div>' : '';
$suc_html = $success ? '<div class="alert-success">' . $success . '</div>' : '';
$uval     = htmlspecialchars($username);
$eval     = htmlspecialchars($email);

$content = <<<HTML
<div class="card" style="max-width:420px; margin:0 auto;">
  <h2>&#128221; Dang ky tai khoan</h2>
  {$err_html}
  {$suc_html}
  <form method="POST" action="/register.php">
    <input type="hidden" name="csrf_token" value="{$csrf}">
    <label>Ten dang nhap <span style="color:#94a3b8;font-weight:400;">(a-z, 0-9, _, 3-50 ky tu)</span></label>
    <input type="text" name="username" value="{$uval}" placeholder="Nhap username..." autocomplete="username">
    <label>Email</label>
    <input type="email" name="email" value="{$eval}" placeholder="Nhap email..." autocomplete="email">
    <label>Mat khau <span style="color:#94a3b8;font-weight:400;">(toi thieu 8 ky tu)</span></label>
    <input type="password" name="password" placeholder="Nhap mat khau..." autocomplete="new-password">
    <label>Xac nhan mat khau</label>
    <input type="password" name="confirm" placeholder="Nhap lai mat khau..." autocomplete="new-password">
    <button type="submit" style="width:100%;">Dang ky</button>
  </form>
  <p class="hint" style="text-align:center;margin-top:12px;">Da co tai khoan? <a href="/login.php">Dang nhap</a></p>
</div>
HTML;

render_layout($content);
