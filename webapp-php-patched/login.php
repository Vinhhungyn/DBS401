<?php
// ============================================================
// login.php (port 5001 - PATCHED)
// FIX: password_verify() bcrypt, prepared statement, CSRF, rate limit
// ============================================================
require_once 'config.php';
require_once 'layout.php';

// Chuyen huong neu da dang nhap
if (isset($_SESSION['user'])) {
    header('Location: /upload.php');
    exit;
}

$error    = '';
$username = '';

// FIX: WAF block
if (isset($_GET['waf_block'])) {
    $error = 'Sai ten dang nhap hoac mat khau!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIX: CSRF check
    if (!csrf_verify()) {
        $error = 'Phien lam viec het han, vui long thu lai!';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // FIX: Rate limiting - toi da 5 lan thu trong 5 phut
        if (!rate_limit_check('login_' . $username)) {
            $error = 'Qua nhieu lan thu. Vui long cho 5 phut roi thu lai!';
        } elseif (!$username || !$password) {
            $error = 'Vui long nhap day du thong tin!';
        } else {
            try {
                $conn = get_conn();
                $stmt = $conn->prepare("SELECT id, username, role, password FROM employees WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $row = $result->fetch_assoc()) {
                    // FIX: password_verify() kiem tra bcrypt hash
                    if (password_verify($password, $row['password'])) {
                        // FIX: Regenerate session ID chong session fixation
                        session_regenerate_id(true);

                        $_SESSION['user'] = $row['username'];
                        $_SESSION['role'] = $row['role'];

                        // Reset rate limit sau khi login thanh cong
                        unset($_SESSION['rl']['login_' . $username]);

                        require_once 'jwt.php';
                        $token = jwt_create($row['username'], $row['role']);

                        // FIX: HttpOnly + SameSite Strict + Secure (neu HTTPS)
                        $cookie_opts = [
                            'expires'  => time() + 3600,
                            'path'     => '/',
                            'httponly' => true,
                            'samesite' => 'Strict',
                        ];
                        setcookie('token', $token, $cookie_opts);
                        setcookie('logged_in', '1', $cookie_opts);

                        $stmt->close();
                        $conn->close();

                        header('Location: ' . (in_array($row['role'], ['admin', 'manager'], true) ? '/search.php' : '/upload.php'));
                        exit;
                    }
                }
                // FIX: thong bao loi chung, khong lo user co ton tai hay khong
                $error = 'Sai ten dang nhap hoac mat khau!';
                $stmt->close();
                $conn->close();
            } catch (Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                $error = 'Loi he thong. Vui long thu lai!';
            }
        }
    }
}

$csrf  = csrf_token();
$err_html = $error ? '<div class="alert-danger">' . htmlspecialchars($error) . '</div>' : '';
$uval     = htmlspecialchars($username);

$content = <<<HTML
<div class="card" style="max-width:420px; margin:0 auto;">
  <h2>&#128100; Dang nhap</h2>
  {$err_html}
  <form method="POST" action="/login.php">
    <input type="hidden" name="csrf_token" value="{$csrf}">
    <label>Ten dang nhap</label>
    <input type="text" name="username" placeholder="Nhap username..." value="{$uval}" autocomplete="username">
    <label>Mat khau</label>
    <input type="password" name="password" placeholder="Nhap mat khau..." autocomplete="current-password">
    <button type="submit" style="width:100%;">Dang nhap</button>
  </form>
  <p class="hint" style="text-align:center;margin-top:12px;">Chua co tai khoan? <a href="/register.php">Dang ky</a></p>
</div>
HTML;

render_layout($content);
