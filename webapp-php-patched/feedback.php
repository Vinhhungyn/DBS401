<?php
// feedback.php (port 5001 - PATCHED)
require_once 'config.php';
require_once 'layout.php';

// Auth check
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$message  = '';
$msg_type = '';

if (!isset($_SESSION['feedbacks'])) $_SESSION['feedbacks'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if (!$name || !$comment) {
        $message  = 'Vui lòng điền đầy đủ thông tin!';
        $msg_type = 'danger';
    } elseif (strlen($name) > 100) {
        $message  = 'Họ tên không được quá 100 ký tự!';
        $msg_type = 'danger';
    } elseif (strlen($comment) > 1000) {
        $message  = 'Nội dung không được quá 1000 ký tự!';
        $msg_type = 'danger';
    } else {
        $_SESSION['feedbacks'][] = [
            'name'    => $name,
            'comment' => $comment,
            'time'    => date('H:i:s d/m/Y'),
        ];
        $message  = 'Gửi phản hồi thành công!';
        $msg_type = 'success';
    }
}

// Reflected XSS - escape output
$search     = $_GET['search'] ?? '';
$search_msg = '';
if ($search !== '') {
    $safe_search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $search_msg  = "<div class='alert-success'>Tìm kiếm: <b>{$safe_search}</b></div>";
}

$msg_html = $message ? "<div class='alert-{$msg_type}'>" . htmlspecialchars($message) . "</div>" : '';

// Stored XSS - escape toan bo output
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
</div>

<div class="card">
  <h2>Gửi phản hồi mới</h2>
  {$msg_html}
  <form method="POST">
    <label>Họ tên <span style="color:#999;font-weight:400;">(tối đa 100 ký tự)</span></label>
    <input type="text" name="name" placeholder="Nhập họ tên..." maxlength="100">
    <label>Nội dung <span style="color:#999;font-weight:400;">(tối đa 1000 ký tự)</span></label>
    <textarea name="comment" rows="4" placeholder="Nhập nội dung..." maxlength="1000"
      style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:15px;resize:vertical;font-family:inherit;"></textarea>
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