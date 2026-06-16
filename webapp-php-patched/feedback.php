<?php
// ============================================================
// feedback.php — Trang gửi phản hồi nội bộ (ĐÃ VÁ XSS)
// Fix: escape tất cả output bằng htmlspecialchars()
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
        $_SESSION['feedbacks'][] = [
            'name'    => $name,
            'comment' => $comment,
            'time'    => date('H:i:s d/m/Y'),
        ];
        $message  = "Gửi phản hồi thành công!";
        $msg_type = 'success';
    }
}

// DA VA: Reflected XSS - escape output
$search     = $_GET['search'] ?? '';
$search_msg = '';
if ($search !== '') {
    $safe_search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $search_msg  = "<div class='alert-success'>Tìm kiếm: <b>{$safe_search}</b></div>";
}

$msg_html = $message ? "<div class='alert-{$msg_type}'>{$message}</div>" : '';

// DA VA: escape toan bo output
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
if (!$feedback_html) $feedback_html = '<p style="color:#999;">Chưa có phản hồi nào.</p>';

$content = <<<HTML
<div class="card">
  <h2>&#128172; Phản hồi nội bộ</h2>
  <form method="GET" style="display:flex;gap:10px;margin-bottom:12px;">
    <input type="text" name="search" placeholder="Tìm kiếm phản hồi..." style="margin:0;flex:1;">
    <button type="submit">Tìm</button>
  </form>
  {$search_msg}
  <p style="font-size:12px;color:#2e7d32;margin-top:8px;">
  
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
</div>

<div class="card">
  <h2>Danh sách phản hồi</h2>
  {$feedback_html}
</div>
HTML;

render_layout($content);
?>