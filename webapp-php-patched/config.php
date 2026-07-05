<?php
// ============================================================
// config.php (port 5001 - PATCHED)
// FIX: JWT_SECRET lay tu env, khong hardcode
// FIX: them ham tao/kiem tra CSRF token
// ============================================================

session_start();

define('DB_HOST', getenv('DB_HOST') ?: 'mysql-patched');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_USER', getenv('DB_USER') ?: 'app_user');
define('DB_PASS', getenv('DB_PASS') ?: 'app123');
define('DB_NAME', getenv('DB_NAME') ?: 'company_db_patched');

// FIX: JWT_SECRET lay tu env, fallback random neu khong co
// Production nen set JWT_SECRET trong docker-compose environment
define('JWT_SECRET', getenv('JWT_SECRET') ?: bin2hex(random_bytes(32)));

function get_conn(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        // FIX: khong lo thong tin loi DB ra ngoai
        error_log('DB connection error: ' . $conn->connect_error);
        die('Loi ket noi he thong. Vui long thu lai sau.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// FIX: CSRF token generation va validation
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

// FIX: Rate limiting don gian cho login (luu trong session)
function rate_limit_check(string $key, int $max = 5, int $window = 300): bool {
    $now = time();
    if (!isset($_SESSION['rl'][$key])) {
        $_SESSION['rl'][$key] = ['count' => 0, 'start' => $now];
    }
    $rl = &$_SESSION['rl'][$key];
    if ($now - $rl['start'] > $window) {
        $rl = ['count' => 0, 'start' => $now];
    }
    $rl['count']++;
    return $rl['count'] <= $max;
}
