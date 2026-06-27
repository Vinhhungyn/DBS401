<?php
// jwt.php - FIXED (giống bản 5001)
define('JWT_SECRET', 'Rnd#9f$Lp2@mXqZ!vK8&wTyN'); // secret mạnh, thay đổi theo ý bạn

function jwt_base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_base64url_decode(string $data): string {
    // Thêm padding cho đúng chuẩn base64
    $padding = 4 - (strlen($data) % 4);
    if ($padding != 4) $data .= str_repeat('=', $padding);
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_create(string $username, string $role): string {
    $header  = jwt_base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = jwt_base64url_encode(json_encode([
        'username' => $username,
        'role'     => $role,
        'iat'      => time(),
        'exp'      => time() + 3600,
    ]));
    $sig = jwt_base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

// HÀM DECODE AN TOÀN: kiểm tra chữ ký và hết hạn
function jwt_decode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;

    // 1. Verify signature
    $expected = jwt_base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) {
        return null; // chữ ký không khớp → từ chối
    }

    // 2. Decode payload
    $data = json_decode(jwt_base64url_decode($payload), true);
    if (!$data) return null;

    // 3. Kiểm tra hết hạn
    if (isset($data['exp']) && $data['exp'] < time()) {
        return null;
    }

    return $data;
}
?>