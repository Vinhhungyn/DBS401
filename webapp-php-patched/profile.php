<?php
// ============================================================
// profile.php (port 5001 - PATCHED)
// FIX: whitelist ext, MIME check, prepared statement, escape output
// FIX: CSRF protection
// FIX: validate path avatar tranh path traversal
// ============================================================
require_once 'config.php';
require_once 'layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$message  = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    // FIX: CSRF check
    if (!csrf_verify()) {
        $message  = 'Phien lam viec het han, vui long thu lai!';
        $msg_type = 'danger';
    } else {
        $file    = $_FILES['avatar'];
        $ext     = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $upload_dir = __DIR__ . '/uploads/';

        // FIX: kiem tra MIME type thuc su bang getimagesize()
        $img_info = @getimagesize($file['tmp_name']);

        if (!in_array($ext, $allowed)) {
            $message  = 'Chi chap nhan: jpg, jpeg, png, gif, webp!';
            $msg_type = 'danger';
        } elseif (!$img_info) {
            $message  = 'File khong phai anh hop le!';
            $msg_type = 'danger';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $message  = 'File qua lon! Toi da 2MB.';
            $msg_type = 'danger';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $message  = 'Loi upload file!';
            $msg_type = 'danger';
        } else {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $safe_name = uniqid('avatar_', true) . '.' . $ext;
            $dest = $upload_dir . $safe_name;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // FIX: validate path avatar cu truoc khi xoa tranh path traversal
                if (!empty($_SESSION['avatar'])) {
                    $old_name = basename($_SESSION['avatar']); // chi lay ten file, khong co path
                    $old_path = realpath($upload_dir . $old_name);
                    $upload_real = realpath($upload_dir);
                    // Dam bao file nam trong thu muc uploads
                    if ($old_path && strncmp($old_path, $upload_real, strlen($upload_real)) === 0) {
                        unlink($old_path);
                    }
                }
                $_SESSION['avatar'] = $safe_name;
                $message  = 'Cap nhat anh dai dien thanh cong!';
                $msg_type = 'success';
            } else {
                $message  = 'Upload that bai!';
                $msg_type = 'danger';
            }
        }
    }
}

$user    = $_SESSION['user'];
$avatar  = $_SESSION['avatar'] ?? null;
$initial = strtoupper(substr($user, 0, 1));

$conn = get_conn();
$stmt = $conn->prepare("SELECT username, role, email, salary FROM employees WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
$conn->close();

$role = $_SESSION['role'] ?? 'user';

$csrf     = csrf_token();
$msg_html = $message ? "<div class='alert-{$msg_type}'>" . htmlspecialchars($message) . "</div>" : '';

$avatar_src  = $avatar ? '/uploads/' . rawurlencode($avatar) : null;
$avatar_html = $avatar_src
    ? "<img src='" . htmlspecialchars($avatar_src) . "' style='width:100%;height:100%;object-fit:cover;'>"
    : "<span style='font-size:48px;font-weight:700;color:white;'>{$initial}</span>";

$salary_fmt = isset($info['salary']) ? number_format($info['salary'], 0, '.', ',') . ' d' : '-';
$safe_email = htmlspecialchars($info['email'] ?? '', ENT_QUOTES, 'UTF-8');

$role_badge = match($role) {
    'admin'   => "<span class='badge badge-admin'>Admin</span>",
    'manager' => "<span class='badge badge-manager'>Manager</span>",
    default   => "<span class='badge badge-user'>User</span>",
};

$content = <<<HTML
<div class="page-header">
  <h1>Ho so ca nhan</h1>
  <p>Quan ly thong tin tai khoan cua ban</p>
</div>

<div style="display:grid; grid-template-columns: 280px 1fr; gap:20px;">
  <div>
    <div class="card" style="text-align:center;">
      <div style="width:100px;height:100px;border-radius:50%;
                  background:linear-gradient(135deg,#2563eb,#0ea5e9);
                  margin:0 auto 16px;overflow:hidden;
                  display:flex;align-items:center;justify-content:center;
                  box-shadow:0 4px 20px rgba(37,99,235,0.3);">
        {$avatar_html}
      </div>
      <div style="font-size:18px;font-weight:700;color:#1e293b;">{$user}</div>
      <div style="margin:6px 0 16px;">{$role_badge}</div>
      {$msg_html}
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="{$csrf}">
        <label for="avatar_input" style="
          display:block;width:100%;padding:10px;
          border:1.5px dashed #cbd5e1;border-radius:8px;
          cursor:pointer;font-size:13px;color:#64748b;
          transition:all 0.2s;text-align:center;margin-bottom:10px;
        " onmouseover="this.style.borderColor='#2563eb';this.style.color='#2563eb'"
           onmouseout="this.style.borderColor='#cbd5e1';this.style.color='#64748b'">
          Chon anh dai dien
        </label>
        <input type="file" name="avatar" id="avatar_input" style="display:none;"
               accept=".jpg,.jpeg,.png,.gif,.webp"
               onchange="this.form.submit()">
      </form>
      <p class="hint">Ho tro: JPG, PNG, GIF, WEBP · Toi da 2MB</p>
    </div>
  </div>

  <div>
    <div class="card">
      <h2>Thong tin ca nhan</h2>
      <table>
        <tr><th>Tham so</th><th>Gia tri</th></tr>
        <tr><td>Username</td><td><b>{$user}</b></td></tr>
        <tr><td>Email</td><td>{$safe_email}</td></tr>
        <tr><td>Vai tro</td><td>{$role_badge}</td></tr>
        <tr><td>Luong</td><td>{$salary_fmt}</td></tr>
      </table>
    </div>
  </div>
</div>
HTML;

render_layout($content);
