<?php
// ============================================================
// config.php (port 5001 - PATCHED)
// FIX: trỏ sang company_db_patched - DB riêng cho bên patched
// Tách riêng để 2 side không ảnh hưởng nhau khi demo
// ============================================================

session_start();

define('DB_HOST', getenv('DB_HOST') ?: 'mysql-patched');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_USER', getenv('DB_USER') ?: 'app_user');
define('DB_PASS', getenv('DB_PASS') ?: 'app123');
// FIX: dùng database riêng company_db_patched thay vì company_db
define('DB_NAME', getenv('DB_NAME') ?: 'company_db_patched');

/**
 * Tạo kết nối MySQL mới
 */
function get_conn(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die('Lỗi kết nối DB: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
