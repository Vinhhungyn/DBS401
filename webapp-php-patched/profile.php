<?php
// ============================================================
// profile.php (port 5001 - PATCHED)
// Fix: whitelist ext, MIME check, prepared statement, escape email
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
    $file    = $_FILES['avatar'];
    $ext     = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $upload_dir = __DIR__ . '/uploads/';

    // Kiem tra MIME type thuc su bang getimagesize()
    $img_info = @getimagesize($file['tmp_name']);

    if (!in_array($ext, $allowed)) {
        $message  = 'Chỉ chấp nhận: jpg, jpeg, png, gif, webp!';
        $msg_type = 'danger';
    } elseif (!$img_info) {
        $message  = 'File không phải ảnh hợp lệ!';
        $msg_type = 'danger';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $message  = 'File quá lớn! Tối đa 2MB.';
        $msg_type = 'danger';
    } else {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $safe_name = uniqid('avatar_', true) . '.' . $ext;
        $dest = $upload_dir . $safe_name;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            if (!empty($_SESSION['avatar'])) {
                $old = $upload_dir . $_SESSION['avatar'];
                if (is_file($old)) unlink($old);
            }
            $_SESSION['avatar'] = $safe_name;
            $message  = 'Cập nhật ảnh đại diện thành công!';
            $msg_type = 'success';
        } else {
            $message  = 'Upload thất bại!';
            $msg_type = 'danger';
        }
    }
}

$user    = $_SESSION['user'];
$avatar  = $_SESSION['avatar'] ?? null;
$initial = strtoupper(substr($user, 0, 1));

// Prepared statement - khong SQLi
$conn = get_conn();
$stmt = $conn->prepare("SELECT username, role, email, salary FROM employees WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
$conn->close();

$role = $_SESSION['role'] ?? 'user';

$msg_html = $message ? "<div class='alert-{$msg_type}'>" . htmlspecialchars($message) . "</div>" : '';

$avatar_src  = $avatar ? "/uploads/{$avatar}" : null;
$avatar_html = $avatar_src
    ? "<img src='" . htmlspecialchars($avatar_src) . "' style='width:100%;height:100%;object-fit:cover;'>"
    : "<span style='font-size:48px;font-weight:700;color:white;'>{$initial}</span>";

$salary_fmt = isset($info['salary']) ? number_format($info['salary'], 0, '.', ',') . ' đ' : '-';

// Escape email tranh Stored XSS
$safe_email = htmlspecialchars($info['email'] ?? '', ENT_QUOTES, 'UTF-8');

$role_badge = match($role) {
    'admin'   => "<span class='badge badge-admin'>Admin</span>",
    'manager' => "<span class='badge badge-manager'>Manager</span>",
    default   => "<span class='badge badge-user'>User</span>",
};

$content = <<<HTML
<div class="page-header">
  <h1>Hồ sơ cá nhân</h1>
  <p>Quản lý thông tin tài khoản của bạn</p>
</div>

<div style="display:grid; grid-template-columns: 280px 1fr; gap:20px;">

  <!-- AVATAR CARD -->
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
        <label for="avatar_input" style="
          display:block; width:100%;
          padding:10px; border:1.5px dashed #cbd5e1;
          border-radius:8px; cursor:pointer;
          font-size:13px; color:#64748b;
          transition:all 0.2s; text-align:center;
          margin-bottom:10px;
        " onmouseover="this.style.borderColor='#2563eb';this.style.color='#2563eb'"
           onmouseout="this.style.borderColor='#cbd5e1';this.style.color='#64748b'">
          📷 Chọn ảnh đại diện
        </label>
        <input type="file" name="avatar" id="avatar_input" style="display:none;"
               accept=".jpg,.jpeg,.png,.gif,.webp"
               onchange="this.form.submit()">
      </form>

      <p class="hint">Hỗ trợ: JPG, PNG, GIF, WEBP · Tối đa 2MB</p>
    </div>
  </div>

  <!-- INFO CARD -->
  <div>
    <div class="card">
      <h2>Thông tin cá nhân</h2>
      <table>
        <tr><th>Tham số</th><th>Giá trị</th></tr>
        <tr><td>Username</td><td><b>{$user}</b></td></tr>
        <tr><td>Email</td><td>{$safe_email}</td></tr>
        <tr><td>Vai trò</td><td>{$role_badge}</td></tr>
        <tr><td>Lương</td><td>{$salary_fmt}</td></tr>
      </table>
    </div>
  </div>

</div>
HTML;

render_layout($content);
?>