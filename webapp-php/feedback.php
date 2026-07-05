<?php
// ============================================================
// feedback.php — Trang gửi phản hồi nội bộ (port 5000 - VULNERABLE)
// LỖ HỔNG CỐ Ý: Reflected XSS + Stored XSS
// Phan quyen: admin xem danh sach, user/manager gui phan hoi
// ============================================================
require_once 'config.php';
require_once 'layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

// Doc role tu JWT (vulnerable: khong verify signature)
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

// Chi user/manager moi duoc gui phan hoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role !== 'admin') {
    $name    = $_POST['name']    ?? '';
    $comment = $_POST['comment'] ?? '';
    if ($name && $comment) {
        // LO HONG: luu truc tiep khong sanitize → Stored XSS
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

// Reflected XSS: hien thi truc tiep tu GET khong escape
$search     = $_GET['search'] ?? '';
$search_msg = '';
if ($search !== '') {
    $search_msg = "<div class='alert-danger'>Tim kiem: <b>{$search}</b></div>";
}

$msg_html = $message ? "<div class='alert-{$msg_type}'>{$message}</div>" : '';

// Danh sach phan hoi (chi admin thay)
$feedback_html = '';
foreach (array_reverse($_SESSION['feedbacks']) as $fb) {
    // LO HONG: khong escape → Stored XSS
    $feedback_html .= "
    <div style='padding:14px 0;border-bottom:1px solid #eee;'>
      <div style='font-weight:600;color:#1a237e;margin-bottom:4px;'>
        {$fb['name']} <span style='font-size:12px;color:#999;font-weight:normal;'>— {$fb['time']}</span>
      </div>
      <div style='color:#444;'>{$fb['comment']}</div>
    </div>";
}
if (!$feedback_html) $feedback_html = '<p style="color:#999;">Chua co phan hoi nao.</p>';

// ============================================================
// HIEN THI THEO ROLE
// Admin: chi thay danh sach phan hoi + search
// User/Manager: chi thay form gui phan hoi
// ============================================================
if ($role === 'admin') {
    // Admin: xem danh sach, co search (Reflected XSS o day)
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
    // User/Manager: chi thay form gui phan hoi
    $content = <<<HTML
<div class="card">
  <h2>&#128172; Gui phan hoi noi bo</h2>
  {$msg_html}
  <form method="POST">
    <label>Ho ten</label>
    <input type="text" name="name" placeholder="Nhap ho ten...">
    <label>Noi dung</label>
    <textarea name="comment" rows="4" placeholder="Nhap noi dung..."
      style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:15px;resize:vertical;"></textarea>
    <button type="submit" style="margin-top:12px;">Gui phan hoi</button>
  </form>
</div>
HTML;
}

render_layout($content);
