<?php
// ============================================================
// layout.php — Base HTML layout
// ============================================================

function render_layout(string $content): void {
  $user = $_SESSION['user'] ?? null;
  $role = $_SESSION['role'] ?? 'guest';
  $avatar = $_SESSION['avatar'] ?? null;

  $nav_user = '';
  if ($user) {
      $avatar_html = $avatar
          ? "<img src='/uploads/{$avatar}' style='width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.4);'>"
          : "<div style='width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;'>". strtoupper(substr($user,0,1)) ."</div>";

      $nav_user = "
        <a href='/profile.php' style='display:flex;align-items:center;gap:8px;text-decoration:none;'>
          {$avatar_html}
          <span style='font-size:14px;color:white;'>{$user}</span>
        </a>
        <a href='/logout.php'>Đăng xuất</a>";

      if ($role === 'admin') {
          $nav_user .= "<a href='/sysconfig.php' style='color:#ffd54f;'>⚙ Cấu hình</a>";
      }
  } else {
      $nav_user = "<a href='/login.php'>Đăng nhập</a>";
  }

  echo <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Company Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --primary:     #2563eb;
    --primary-dark:#1d4ed8;
    --primary-light:#eff6ff;
    --accent:      #0ea5e9;
    --success:     #10b981;
    --danger:      #ef4444;
    --warning:     #f59e0b;
    --gray-50:     #f8fafc;
    --gray-100:    #f1f5f9;
    --gray-200:    #e2e8f0;
    --gray-400:    #94a3b8;
    --gray-600:    #475569;
    --gray-800:    #1e293b;
    --shadow-sm:   0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md:   0 4px 16px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
    --shadow-lg:   0 10px 40px rgba(0,0,0,0.10), 0 4px 12px rgba(0,0,0,0.06);
    --radius:      12px;
    --radius-sm:   8px;
  }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--gray-50);
    color: var(--gray-800);
    min-height: 100vh;
  }

  /* NAVBAR */
  .navbar {
    background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #0ea5e9 100%);
    padding: 0 32px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 20px rgba(37,99,235,0.3);
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .navbar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
  }

  .navbar-logo {
    width: 36px; height: 36px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    backdrop-filter: blur(10px);
  }

  .navbar-title {
    font-size: 17px;
    font-weight: 700;
    color: white;
    letter-spacing: -0.3px;
  }

  .navbar-title span {
    color: #bfdbfe;
    font-weight: 400;
  }

  .navbar-nav {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .navbar-nav a {
    color: rgba(255,255,255,0.85);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    padding: 7px 14px;
    border-radius: 8px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .navbar-nav a:hover {
    background: rgba(255,255,255,0.15);
    color: white;
  }

  .navbar-divider {
    width: 1px; height: 24px;
    background: rgba(255,255,255,0.2);
    margin: 0 6px;
  }

  /* CONTAINER */
  .container {
    max-width: 960px;
    margin: 36px auto;
    padding: 0 20px;
  }

  /* CARD */
  .card {
    background: white;
    border-radius: var(--radius);
    padding: 32px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    margin-bottom: 20px;
    transition: box-shadow 0.2s;
  }

  .card:hover { box-shadow: var(--shadow-md); }

  h2 {
    color: var(--gray-800);
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    letter-spacing: -0.3px;
  }

  /* FORM */
  label {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-600);
    display: block;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  input[type=text],
  input[type=password],
  input[type=file] {
    width: 100%;
    padding: 11px 14px;
    margin-bottom: 16px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius-sm);
    font-size: 15px;
    font-family: inherit;
    color: var(--gray-800);
    background: var(--gray-50);
    transition: all 0.2s;
    outline: none;
  }

  input[type=text]:focus,
  input[type=password]:focus {
    border-color: var(--primary);
    background: white;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
  }

  button, .btn {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    color: white;
    padding: 11px 28px;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 15px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(37,99,235,0.25);
    letter-spacing: -0.1px;
  }

  button:hover, .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(37,99,235,0.35);
  }

  button:active { transform: translateY(0); }

  /* ALERTS */
  .alert-danger {
    background: #fef2f2;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: var(--radius-sm);
    margin-bottom: 16px;
    border-left: 3px solid #ef4444;
    font-size: 14px;
    font-weight: 500;
  }

  .alert-success {
    background: #f0fdf4;
    color: #16a34a;
    padding: 12px 16px;
    border-radius: var(--radius-sm);
    margin-bottom: 16px;
    border-left: 3px solid #10b981;
    font-size: 14px;
    font-weight: 500;
  }

  /* TABLE */
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }

  th {
    background: var(--gray-50);
    color: var(--gray-600);
    padding: 10px 14px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--gray-200);
  }

  td {
    padding: 13px 14px;
    border-bottom: 1px solid var(--gray-100);
    font-size: 14px;
  }

  tr:last-child td { border-bottom: none; }
  tr:hover td { background: var(--primary-light); }

  /* BADGES */
  .badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
  }

  .badge-admin   { background: #fce7f3; color: #be185d; }
  .badge-user    { background: #eff6ff; color: #1d4ed8; }
  .badge-manager { background: #f3e8ff; color: #7e22ce; }

  /* MISC */
  .hint { font-size: 12px; color: var(--gray-400); margin-top: 8px; }
  code {
    font-family: 'JetBrains Mono', 'Courier New', monospace;
    background: var(--gray-100);
    padding: 2px 7px;
    border-radius: 4px;
    font-size: 13px;
    color: #be185d;
  }

  a { color: var(--primary); }

  /* PAGE HEADER */
  .page-header {
    margin-bottom: 24px;
  }
  .page-header h1 {
    font-size: 26px;
    font-weight: 700;
    color: var(--gray-800);
    letter-spacing: -0.5px;
  }
  .page-header p {
    color: var(--gray-400);
    font-size: 14px;
    margin-top: 4px;
  }
</style>
</head>
<body>

<nav class="navbar">
  <a href="/search.php" class="navbar-brand">
    <div class="navbar-logo">🏢</div>
    <div class="navbar-title">Company <span>Portal</span></div>
  </a>

  <div class="navbar-nav">
    <a href="/search.php">👥 Nhân viên</a>
    <a href="/upload.php">📎 Tài liệu</a>
    <div class="navbar-divider"></div>
    {$nav_user}
  </div>
</nav>

<div class="container">
  {$content}
</div>

</body>
</html>
HTML;
}