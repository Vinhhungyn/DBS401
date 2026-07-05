<?php
// feedback.php (port 5001 - PATCHED)
// FIX: htmlspecialchars chong XSS
// Phan quyen: admin xem danh sach, user/manager gui phan hoi
require_once 'config.php';
require_once 'layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

// Doc role tu JWT (patched: co verify signature)
$role = 'user';
if (isset($_COOKIE['token'])) {
    require_once 'jwt.php';
    $payload = jwt_decode($_COOKIE['token']);
    if ($payload && isset($payload['role'])) {
        $role = $payload['role'];
    }
}

$message  = '';
$msg_type = '';

if (!isset($_SESSION['feedbacks'])) $_SESSION['feedbacks'] = [];

// WAF block
if (isset($_GET['waf_block'])) {
    $message  = 'Noi dung phan hoi khong hop le, vui long kiem tra lai!';
    $msg_type = 'danger';
}

// Chi user/manager moi duoc gui phan hoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role !== 'admin') {
    $name    = trim($_POST['name']    ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if (!$name || !$comment) {
        $message  = 'Vui long dien day du thong tin!';
        $msg_type = 'danger';
    } elseif (strlen($name) > 100) {
        $message  = 'Ho ten khong duoc qua 100 ky tu!';
        $msg_type = 'danger';
    } elseif (strlen($comment) > 1000) {
        $message  = 'Noi dung khong duoc qua 1000 ky tu!';
        $msg_type = 'danger';
    } else {
        $_SESSION['feedbacks'][] = [
            'name'    => $name,
            'comment' => $comment,
            'time'    => date('H:i:s d/m/Y'),
            'user'    => $_SESSION['user'],
        ];
        $message  = 'Gui phan hoi thanh cong!';
        $msg_type = 'success';
    }
}

// FIX: escape search de chong Reflected XSS
$search     = $_GET['search'] ?? '';
$search_msg = '';
if ($search !== '') {
    $safe_search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $search_msg  = "<div class='alert-success'>Tim kiem: <b>{$safe_search}</b></div>";
}

$msg_html = $message ? "<div class='alert-{$msg_type}'>" . htmlspecialchars($message) . "</div>" : '';

// Danh sach phan hoi (chi admin thay) - FIX: escape tat ca output
$feedback_html = '';
foreach (array_reverse($_SESSION['feedbacks']) as $fb) {
    $safe_name    = htmlspecialchars($fb['name'],    ENT_QUOTES, 'UTF-8');
    $safe_comment = htmlspecialchars($fb['comment'], ENT_QUOTES, 'UTF-8');
    $safe_time    = htmlspecialchars($fb['time'],    ENT_QUOTES, 'UTF-8');
    $feedback_html .= "
    <div style='padding:14px 0;border-bottom:1px solid #eee;'>
      <div style='font-weight:600;color:#1a237e;margin-bottom:4px;'>
        {$safe_name} <span style='font-size:12px;color:#999;font-weight:normal;'>— {$safe_time}</span>
      </div>
      <div style='color:#444;'>{$safe_comment}</div>
    </div>";
}
if (!$feedback_html) $feedback_html = '<p style="color:#999;">Chua co phan hoi nao.</p>';

// ============================================================
// HIEN THI THEO ROLE
// Admin: chi thay danh sach phan hoi + search
// User/Manager: chi thay form gui phan hoi
// ============================================================
if ($role === 'admin') {
    $content = <<<HTML
<div class="card">
  <h2>&#128172; Quan ly phan hoi</h2>
  <form method="GET" style="display:flex;gap:10px;margin-bottom:12px;">
    <input type="text" name="search" placeholder="Tim kiem phan hoi..." style="margin:0;flex:1;">
    <button type="submit">Tim</button>
  </form>
  {$search_msg}
</div>

<div class="card">
  <h2>Danh sach phan hoi</h2>
  {$feedback_html}
</div>
HTML;
} else {
    $content = <<<HTML
<div class="card">
  <h2>&#128172; Gui phan hoi noi bo</h2>
  {$msg_html}
  <form method="POST">
    <label>Ho ten</label>
    <input type="text" name="name" placeholder="Nhap ho ten..." maxlength="100">
    <label>Noi dung</label>
    <textarea name="comment" rows="4" placeholder="Nhap noi dung..." maxlength="1000"
      style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:15px;resize:vertical;font-family:inherit;"></textarea>
    <button type="submit" style="margin-top:12px;">Gui phan hoi</button>
  </form>
</div>
HTML;
}

render_layout($content);
