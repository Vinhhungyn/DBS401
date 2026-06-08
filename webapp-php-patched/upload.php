<?php
// ============================================================
// upload.php — Trang upload file
// LỖ HỔNG CỐ Ý: không kiểm tra loại file, cho phép upload .php
// Red Team upload webshell → thực thi lệnh hệ thống
// ============================================================
require_once 'config.php';
require_once 'layout.php';

$message = '';
$msg_type = '';
$uploaded_url = '';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file     = $_FILES['file'];
    $filename = basename($file['name']);
    $upload_dir = __DIR__ . '/uploads/';

    // Tao thu muc uploads neu chua co
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // LO HONG CO Y: khong kiem tra extension, khong kiem tra MIME type
    // Cho phep upload bat ky loai file ke ca .php
    $dest = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $uploaded_url = '/uploads/' . $filename;
        $message  = "Upload thành công! File: <a href='{$uploaded_url}' target='_blank'>{$filename}</a>";
        $msg_type = 'success';
    } else {
        $message  = 'Upload thất bại!';
        $msg_type = 'danger';
    }
}

$msg_html = $message
    ? "<div class='alert-{$msg_type}'>{$message}</div>"
    : '';

$shell_url = $uploaded_url ? "<div class='alert-danger'>
    &#9888; Thực thi lệnh: <a href='{$uploaded_url}?cmd=id' target='_blank'>{$uploaded_url}?cmd=id</a><br>
    Thử: <code>{$uploaded_url}?cmd=ls /var/www/html</code><br>
    Thử: <code>{$uploaded_url}?cmd=cat /var/www/html/config.php</code>
</div>" : '';

$content = <<<HTML
<div class="card">
  <h2>&#128196; Upload tài liệu nội bộ</h2>
  {$msg_html}
  {$shell_url}
  <form method="POST" enctype="multipart/form-data">
    <label>Chọn file để upload</label>
    <input type="file" name="file" style="margin: 8px 0 16px; width:100%;">
    <button type="submit">Upload</button>
  </form>
  <p class="hint">Hỗ trợ: PDF, DOC, JPG... (tất cả định dạng)</p>

</div>


HTML;

render_layout($content);
?>