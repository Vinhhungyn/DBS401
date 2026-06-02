<?php
// ============================================================
// config.php — Cấu hình kết nối DB & khởi động session
// Tương đương: biến DB_HOST/DB_USER/DB_PASS/DB_NAME trong app.py
// ============================================================

session_start();

define('DB_HOST', getenv('DB_HOST') ?: 'mysql_vuln');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_USER', getenv('DB_USER') ?: 'app_user');
define('DB_PASS', getenv('DB_PASS') ?: 'app123');
define('DB_NAME', 'company_db');

/**
 * Tạo kết nối MySQL mới — tương đương hàm get_conn() trong app.py
 */
function get_conn(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die('Lỗi kết nối DB: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
