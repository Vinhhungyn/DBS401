<?php
// ============================================================
// upload.php (port 5001 - PATCHED)
// Fix: whitelist ext, an duong dan, chong path traversal
// ============================================================
require_once 'config.php';
require_once 'layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$message  = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file     = $_FILES['file'];
    $filename = basename($file['name']);
    $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png'];
    $upload_dir = __DIR__ . '/uploads/';

    // Chong path traversal: basename() + kiem tra ext
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        $message  = 'Tên file không hợp lệ!';
        $msg_type = 'danger';
    } elseif (!in_array($ext, $allowed)) {
        $message  = 'Chỉ chấp nhận: jpg, jpeg, png!';
        $msg_type = 'danger';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $message  = 'File quá lớn! Tối đa 2MB.';
        $msg_type = 'danger';
    } else {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        // Doi ten ngau nhien - khong expose ten goc
        $safe_name = uniqid('file_', true) . '.' . $ext;
        $dest = $upload_dir . $safe_name;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Khong hien duong dan, chi thong bao thanh cong
            $message  = 'Upload thành công!';
            $msg_type = 'success';
        } else {
            $message  = 'Upload thất bại!';
            $msg_type = 'danger';
        }
    }
}

$msg_html = $message ? "<div class='alert-{$msg_type}'>" . htmlspecialchars($message) . "</div>" : '';

$content = <<<HTML
<div class="card">
  <h2>&#128196; Upload tài liệu nội bộ</h2>
  {$msg_html}
  <form method="POST" enctype="multipart/form-data">
    <label>Chọn file để upload</label>
    <input type="file" name="file" accept=".jpg,.jpeg,.png" style="margin: 8px 0 16px; width:100%;">
    <button type="submit">Upload</button>
  </form>
  <p class="hint">Hỗ trợ: JPG, JPEG, PNG · Tối đa 2MB</p>
</div>
HTML;

render_layout($content);
?>