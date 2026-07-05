<?php
// ============================================================
// feedback.php (port 5000 - VULNERABLE)
// LO HONG CO Y: Stored XSS + Reflected XSS
// Luu feedback vao file JSON de share giua cac session
// ============================================================
require_once 'config.php';
require_once 'layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

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

// Luu feedback vao file JSON (share giua tat ca session)
$feedback_file = '/tmp/feedbacks.json';
$feedbacks = [];
if (file_exists($feedback_file)) {
    $feedbacks = json_decode(file_get_contents($feedback_file), true) ?? [];
}

// Chi user/manager moi duoc gui phan hoi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role !== 'admin') {
    $name    = $_POST['name']    ?? '';
    $comment = $_POST['comment'] ?? '';
    if ($name && $comment) {
        // LO HONG: luu truc tiep khong sanitize -> Stored XSS
        $feedbacks[] = [
            'name'    => $name,
            'comment' => $comment,
            'time'    => date('H:i:s d/m/Y'),
            'user'    => $_SESSION['user'],
        ];
        file_put_contents($feedback_file, json_encode($feedbacks));
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

// Danh sach phan hoi (chi admin thay) - khong escape = Stored XSS
$feedback_html = '';
foreach (array_reverse($feedbacks) as $fb) {
    $feedback_html .= "
    <div style='padding:14px 0;border-bottom:1px solid #eee;'>
      <div style='font-weight:600;color:#1a237e;margin-bottom:4px;'>
        {$fb['name']} <span style='font-size:12px;color:#999;font-weight:normal;'>- {$fb['time']}</span>
      </div>
      <div style='color:#444;'>{$fb['comment']}</div>
    </div>";
}
if (!$feedback_html) $feedback_html = '<p style="color:#999;">Chua co phan hoi nao.</p>';

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
