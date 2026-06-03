<?php
// ============================================================
// upload.php — Trang upload file (ĐÃ VÁ TOÀN DIỆN)
// Fix: blacklist + double extension + hoa/thường + path traversal
//      + .htaccess + null byte + MIME type
// ============================================================
require_once 'config.php';
require_once 'layout.php';

$message  = '';
$msg_type = '';

if (!isset($_SESSION['user'])) {
  header('Location: /login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file     = $_FILES['file'];
    $upload_dir = __DIR__ . '/uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // VA 1: Lay ten file, loai bo path traversal (../../)
    $filename = basename($file['name']);

    // VA 2: Loai bo null byte
    $filename = str_replace("\0", '', $filename);

    // VA 3: Lay extension, lowercase de tranh bypass hoa/thuong
    // Lay extension thuc su (tranh double extension: shell.php.jpg)
    $parts = explode('.', $filename);
    // Kiem tra tat ca cac phan extension (khong chi cuoi cung)
    $all_exts = array_slice($parts, 1); // bo phan ten file
    $all_exts = array_map('strtolower', $all_exts);

    // VA 4: Blacklist toan dien
    $blacklist = [
        // PHP variants
        'php', 'php3', 'php4', 'php5', 'php7', 'php8',
        'phtml', 'pht', 'phar', 'inc', 'phps',
        // Server-side khac
        'asp', 'aspx', 'asa', 'asax', 'ashx', 'asmx',
        'jsp', 'jspx', 'jsw', 'jsv',
        'cfm', 'cfml', 'cfc',
        // Script
        'sh', 'bash', 'zsh', 'fish',
        'py', 'pyc', 'pyo',
        'pl', 'pm', 'cgi',
        'rb', 'rbw',
        'exe', 'dll', 'so', 'bin',
        // Config override
        'htaccess', 'htpasswd', 'user.ini', 'web.config',
        // Null byte / special
        'shtml', 'shtm',
    ];

    $blocked = false;
    foreach ($all_exts as $e) {
        if (in_array($e, $blacklist)) {
            $blocked = true;
            break;
        }
    }

    // VA 5: Kiem tra MIME type (khong tin Content-Type tu client)
    $allowed_mime = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    $mime = mime_content_type($file['tmp_name']);

    if ($blocked) {
        $message  = "Lỗi: File không được phép upload!";
        $msg_type = 'danger';
    } elseif (!in_array($mime, $allowed_mime)) {
        $message  = "Lỗi: Loại file không hợp lệ (MIME: {$mime})!";
        $msg_type = 'danger';
    } else {
        // VA 6: Doi ten file ngau nhien de tranh ghi de + doan duong dan
        $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $safe_name = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest      = $upload_dir . $safe_name;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $message  = "Upload thành công! File đã lưu an toàn.";
            $msg_type = 'success';
        } else {
            $message  = 'Upload thất bại!';
            $msg_type = 'danger';
        }
    }
}

$msg_html = $message ? "<div class='alert-{$msg_type}'>{$message}</div>" : '';

$content = <<<HTML
<div class="card">
  <h2>&#128196; Upload tài liệu nội bộ</h2>
  {$msg_html}
  <form method="POST" enctype="multipart/form-data">
    <label>Chọn file để upload</label>
    <input type="file" name="file" style="margin: 8px 0 16px; width:100%;">
    <button type="submit">Upload</button>
  </form>
  <p class="hint">Chấp nhận: PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, TXT</p>

</div>
HTML;

render_layout($content);
?>