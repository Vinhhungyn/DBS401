<?php
// jwt.php - PATCHED (5001)
// Verify signature day du, secret key manh, kiem tra exp

define('JWT_SECRET', 'Rnd#9f$Lp2@mXqZ!vK8&wTyN'); // secret key manh

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

// PATCHED: verify signature + kiem tra exp
function jwt_decode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;

    // Verify signature
    $expected = jwt_base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null; // signature sai

    $data = json_decode(jwt_base64url_decode($payload), true);
    if (!$data) return null;

    // Kiem tra het han
    if (isset($data['exp']) && $data['exp'] < time()) return null;

    return $data;
}
