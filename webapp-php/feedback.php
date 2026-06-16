<?php
// ============================================================
// feedback.php — Trang gửi phản hồi nội bộ
// LỖ HỔNG CỐ Ý: Reflected XSS + Stored XSS
// ============================================================
require_once 'config.php';
require_once 'layout.php';

$message = '';
$msg_type = '';

if (!isset($_SESSION['feedbacks'])) $_SESSION['feedbacks'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $_POST['name']    ?? '';
    $comment = $_POST['comment'] ?? '';
    if ($name && $comment) {
        // LO HONG: luu truc tiep khong sanitize → Stored XSS
        $_SESSION['feedbacks'][] = [
            'name'    => $name,
            'comment' => $comment,
            'time'    => date('H:i:s d/m/Y'),
        ];
        $message  = "Gửi phản hồi thành công!";
        $msg_type = 'success';
    }
}

// Reflected XSS: hien thi truc tiep tu GET khong escape
$search     = $_GET['search'] ?? '';
$search_msg = '';
if ($search !== '') {
    $search_msg = "<div class='alert-danger'>Tìm kiếm: <b>{$search}</b></div>";
}

$msg_html = $message ? "<div class='alert-{$msg_type}'>{$message}</div>" : '';

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
if (!$feedback_html) $feedback_html = '<p style="color:#999;">Chưa có phản hồi nào.</p>';

$content = <<<HTML
<div class="card">
  <h2>&#128172; Phản hồi nội bộ</h2>
  <form method="GET" style="display:flex;gap:10px;margin-bottom:12px;">
    <input type="text" name="search" placeholder="Tìm kiếm phản hồi..." style="margin:0;flex:1;">
    <button type="submit">Tìm</button>
  </form>
  {$search_msg}
  <p style="font-size:12px;color:#e53935;margin-top:8px;">
   
  </p>
</div>

<div class="card">
  <h2>Gửi phản hồi mới</h2>
  {$msg_html}
  <form method="POST">
    <label>Họ tên</label>
    <input type="text" name="name" placeholder="Nhập họ tên...">
    <label>Nội dung</label>
    <textarea name="comment" rows="4" placeholder="Nhập nội dung..."
      style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:15px;resize:vertical;"></textarea>
    <button type="submit" style="margin-top:12px;">Gửi phản hồi</button>
  </form>
  <p style="font-size:12px;color:#e53935;margin-top:8px;">
  
  </p>
</div>

<div class="card">
  <h2>Danh sách phản hồi</h2>
  {$feedback_html}
</div>
HTML;

render_layout($content);
?>