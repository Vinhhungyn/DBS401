<?php
// ============================================================
// upload.php (port 5001 - PATCHED)
// Fix: whitelist extension, doi ten file bang uniqid, bo $shell_url debug block
// ============================================================
require_once 'config.php';
require_once 'layout.php';

$message = '';
$msg_type = '';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file    = $_FILES['file'];
    $ext     = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx','jpg', 'jpeg', 'png'];
    $upload_dir = __DIR__ . '/uploads/';

    if (!in_array($ext, $allowed)) {
        $message  = 'Chi chap nhan: pdf, doc, docx,jpg, jpeg, png!';
        $msg_type = 'danger';
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $message  = 'File qua lon! Toi da 5MB.';
        $msg_type = 'danger';
    } else {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        // Doi ten file hoan toan bang uniqid - khong giu ten goc, chong path traversal
        $safe_name = uniqid('file_', true) . '.' . $ext;
        $dest = $upload_dir . $safe_name;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $message  = 'Upload thanh cong! File: ' . htmlspecialchars($safe_name);
            $msg_type = 'success';
        } else {
            $message  = 'Upload that bai!';
            $msg_type = 'danger';
        }
    }
}

$msg_html = $message
    ? "<div class='alert-{$msg_type}'>" . $message . "</div>"
    : '';

$content = <<<HTML
<div class="card">
  <h2>&#128196; Upload tai lieu noi bo</h2>
  {$msg_html}
  <form method="POST" enctype="multipart/form-data">
    <label>Chon file de upload</label>
    <input type="file" name="file" style="margin: 8px 0 16px; width:100%;">
    <button type="submit">Upload</button>
  </form>
  <p class="hint">Ho tro: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (toi da 5MB)</p>
</div>
HTML;

render_layout($content);
?>