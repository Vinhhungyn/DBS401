<?php
// ============================================================
// upload.php (port 5001 - PATCHED)
// FIX: whitelist extension, MIME check, doi ten file, kiem tra size
// FIX: CSRF protection
// FIX: WAF block -> hien loi tu nhien
// ============================================================
require_once 'config.php';
require_once 'layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

$message  = '';
$msg_type = '';

// FIX: WAF block
if (isset($_GET['waf_block'])) {
    $message  = 'File khong hop le hoac khong duoc ho tro!';
    $msg_type = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // FIX: CSRF check
    if (!csrf_verify()) {
        $message  = 'Phien lam viec het han, vui long thu lai!';
        $msg_type = 'danger';
    } else {
        $file    = $_FILES['file'];
        $ext     = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
        $allowed_ext  = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        // FIX: kiem tra MIME type thuc su
        $allowed_mime = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
        ];
        $upload_dir = __DIR__ . '/uploads/';

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($ext, $allowed_ext)) {
            $message  = 'Chi chap nhan: pdf, doc, docx, jpg, jpeg, png!';
            $msg_type = 'danger';
        } elseif (!in_array($mime, $allowed_mime)) {
            $message  = 'File khong hop le (MIME type bi tu choi)!';
            $msg_type = 'danger';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $message  = 'File qua lon! Toi da 5MB.';
            $msg_type = 'danger';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $message  = 'Loi upload file!';
            $msg_type = 'danger';
        } else {
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            // FIX: doi ten file bang uniqid tranh overwrite
            $safe_name = uniqid('file_', true) . '.' . $ext;
            $dest = $upload_dir . $safe_name;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $message  = 'Upload thanh cong!';
                $msg_type = 'success';
            } else {
                $message  = 'Upload that bai!';
                $msg_type = 'danger';
            }
        }
    }
}

$csrf     = csrf_token();
$msg_html = $message ? "<div class='alert-{$msg_type}'>" . htmlspecialchars($message) . "</div>" : '';

$content = <<<HTML
<div class="card">
  <h2>&#128196; Upload tai lieu noi bo</h2>
  {$msg_html}
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="{$csrf}">
    <label>Chon file de upload</label>
    <input type="file" name="file" style="margin: 8px 0 16px; width:100%;">
    <button type="submit">Upload</button>
  </form>
  <p class="hint">Ho tro: PDF, DOC, DOCX, JPG, PNG (toi da 5MB)</p>
</div>
HTML;

render_layout($content);
