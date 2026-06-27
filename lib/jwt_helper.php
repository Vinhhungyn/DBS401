<?php
/**
 * JWT Helper functions for HS256 algorithm
 */

/**
 * Encode data into JWT using HS256
 * @param array $payload Data to encode in JWT payload
 * @param int $expiry Seconds until expiration (default 3600 = 1 hour)
 * @return string JWT token
 */
function jwt_encode(array $payload, int $expiry = 3600): string {
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);

    // Add standard claims
    $payload = array_merge($payload, [
        'iat' => time(),
        'exp' => time() + $expiry,
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

    $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
}

/**
 * Decode and verify JWT using HS256
 * @param string $token JWT token to verify
 * @return array|false Payload nếu hợp lệ, false nếu không
 */
function jwt_decode(string $token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    [$header64, $payload64, $signature64] = $parts;

    // Fix padding
    $header64 = str_replace(['-', '_'], ['+', '/'], $header64);
    $payload64 = str_replace(['-', '_'], ['+', '/'], $payload64);
    $signature64 = str_replace(['-', '_'], ['+', '/'], $signature64);

    // Add padding if needed
    $header64 .= str_repeat('=', (4 - strlen($header64) % 4) % 4);
    $payload64 .= str_repeat('=', (4 - strlen($payload64) % 4) % 4);
    $signature64 .= str_repeat('=', (4 - strlen($signature64) % 4) % 4);

    $header = json_decode(base64_decode($header64), true);
    $payload = json_decode(base64_decode($payload64), true);
    $signature = base64_decode($signature64);

    // Verify header
    if (!$header || $header['alg'] !== 'HS256' || $header['typ'] !== 'JWT') {
        return false;
    }

    // Verify signature
    $expectedSignature = hash_hmac('sha256', $header64 . '.' . $payload64, JWT_SECRET, true);
    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }

    // Verify expiration
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}