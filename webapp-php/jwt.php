<?php
// jwt.php - VULNERABLE (5000)
// LO HONG CO Y: secret key yeu + KHONG verify signature khi decode

define('JWT_SECRET', 'secret'); // LO HONG: secret key qua yeu

function jwt_base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
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

// LO HONG CO Y: chi decode, KHONG verify signature
// Attacker co the sua role trong payload roi encode lai
function jwt_decode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    $payload = json_decode(jwt_base64url_decode($parts[1]), true);
    if (!$payload) return null;
    // KHONG kiem tra signature!
    // KHONG kiem tra exp!
    return $payload;
}