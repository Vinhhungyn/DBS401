<?php
// feedback.php (port 5001 - PATCHED)
// FIX: WAF block -> hien "Noi dung khong hop le" thay vi 403
require_once 'config.php';
require_once 'layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$message  = '';
$msg_type = '';

// FIX: WAF block -> hien thong bao loi feedback tu nhien
if (isset($_GET['waf_block'])) {
    $message  = 'Noi dung phan hoi khong hop le, vui long kiem tra lai!';
    $msg_type = 'danger';
}

if (!isset($_SESSION['feedbacks'])) $_SESSION['feedbacks'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        ];
        $message  = 'Gui phan hoi thanh cong!';
        $msg_type = 'success';
    }
}

$search     = $_GET['search'] ?? '';
$search_msg = '';
if ($search !== '') {
    $safe_search = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
    $search_msg  = "<div class='alert-success'>Tim kiem: <b>{$safe_search}</b></div>";
}

$msg_html = $message ? "<div class='alert-{$msg_type}'>" . htmlspecialchars($message) . "</div>" : '';

$feedback_html = '';
foreach (array_reverse($_SESSION['feedbacks']) as $fb) {
    $safe_name    = htmlspecialchars($fb['name'],    ENT_QUOTES, 'UTF-8');
    $safe_comment = htmlspecialchars($fb['comment'], ENT_QUOTES, 'UTF-8');
    $safe_time    = htmlspecialchars($fb['time'],    ENT_QUOTES, 'UTF-8');
    $feedback_html .= "
    <div style='padding:14px 0;border-bottom:1px solid #eee;'>
      <div style='font-weight:600;color:#1a237e;margin-bottom:4px;'>
        {$safe_name} <span style='font-size:12px;color:#999;font-weight:normal;'>- {$safe_time}</span>
      </div>
      <div style='color:#444;'>{$safe_comment}</div>
    </div>";
}
if (!$feedback_html) $feedback_html = '<p style="color:#999;">Chua co phan hoi nao.</p>';

$content = <<<HTML
<div class="card">
  <h2>&#128172; Phan hoi noi bo</h2>
  <form method="GET" style="display:flex;gap:10px;margin-bottom:12px;">
    <input type="text" name="search" placeholder="Tim kiem phan hoi..." style="margin:0;flex:1;">
    <button type="submit">Tim</button>
  </form>
  {$search_msg}
</div>

<div class="card">
  <h2>Gui phan hoi moi</h2>
  {$msg_html}
  <form method="POST">
    <label>Ho ten <span style="color:#999;font-weight:400;">(toi da 100 ky tu)</span></label>
    <input type="text" name="name" placeholder="Nhap ho ten..." maxlength="100">
    <label>Noi dung <span style="color:#999;font-weight:400;">(toi da 1000 ky tu)</span></label>
    <textarea name="comment" rows="4" placeholder="Nhap noi dung..." maxlength="1000"
      style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:15px;resize:vertical;font-family:inherit;"></textarea>
    <button type="submit" style="margin-top:12px;">Gui phan hoi</button>
  </form>
</div>

<div class="card">
  <h2>Danh sach phan hoi</h2>
  {$feedback_html}
</div>
HTML;

render_layout($content);
