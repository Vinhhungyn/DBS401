<?php
// ============================================================
// layout.php — Base HTML layout dùng chung cho mọi trang
// Tương đương: biến BASE + render_template_string() trong app.py
// ============================================================

function render_layout(string $content): void {
    $user = $_SESSION['user'] ?? null;
    $nav_user = '';
    if ($user) {
        $u = htmlspecialchars($user);
        $nav_user = "<span style='font-size:14px;'>Xin chào, <b>{$u}</b></span>"
                  . "<a href='/logout.php'>Đăng xuất</a>";
    } else {
        $nav_user = "<a href='/login.php'>Đăng nhập</a>";
    }

    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Company Internal Portal</title>
<style>
  body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; }
  .navbar { background: #1a237e; color: white; padding: 14px 32px;
            display: flex; align-items: center; justify-content: space-between; }
  .navbar a { color: #90caf9; text-decoration: none; margin-left: 16px; font-size: 14px; }
  .container { max-width: 900px; margin: 40px auto; padding: 0 16px; }
  .card { background: white; border-radius: 8px; padding: 32px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 24px; }
  h2 { color: #1a237e; margin-top: 0; }
  input[type=text], input[type=password] {
    width: 100%; padding: 10px; margin: 8px 0 16px;
    border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 15px; }
  button { background: #1a237e; color: white; padding: 10px 28px;
           border: none; border-radius: 4px; font-size: 15px; cursor: pointer; }
  button:hover { background: #283593; }
  .alert-danger  { background: #ffebee; color: #c62828; padding: 12px 16px;
                   border-radius: 4px; margin-bottom: 16px; border-left: 4px solid #c62828; }
  .alert-success { background: #e8f5e9; color: #2e7d32; padding: 12px 16px;
                   border-radius: 4px; margin-bottom: 16px; border-left: 4px solid #2e7d32; }
  table { width: 100%; border-collapse: collapse; margin-top: 16px; }
  th { background: #1a237e; color: white; padding: 10px 14px; text-align: left; }
  td { padding: 10px 14px; border-bottom: 1px solid #eee; }
  tr:hover td { background: #f5f5f5; }
  .badge { display: inline-block; padding: 2px 10px; border-radius: 99px;
           font-size: 12px; font-weight: bold; }
  .badge-admin   { background: #fce4ec; color: #880e4f; }
  .badge-user    { background: #e3f2fd; color: #0d47a1; }
  .badge-manager { background: #f3e5f5; color: #4a148c; }
  .hint { font-size: 12px; color: #999; margin-top: 6px; }
  code { font-family: monospace; }
</style>
</head>
<body>
<div class="navbar">
  <span style="font-size:18px; font-weight:bold;">&#128274; Company Portal</span>
  <div>
    {$nav_user}
    <a href="/search.php">Tìm nhân viên</a>

  <!--  <a href="/sysconfig.php">Cấu hình hệ thống</a> -->
    
  </div>
</div>
<div class="container">
  {$content}
</div>
</body>
</html>
HTML;
}
