<?php
declare(strict_types=1);

function tokenSecret(): string
{
    $configuredSecret = getenv('CAMPUSCARE_JWT_SECRET');

    if (is_string($configuredSecret) && trim($configuredSecret) !== '') {
        return trim($configuredSecret);
    }

    // Keep local development working without shipping a public shared secret.
    return hash('sha256', __DIR__ . '|' . (getenv('COMPUTERNAME') ?: php_uname('n')));
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64UrlDecode(string $value): string
{
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return (string) base64_decode(strtr($value, '-_', '+/'));
}

function createToken(array $payload, int $ttlSeconds = 86400): string
{
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT',
    ];

    $issuedAt = time();
    $tokenPayload = array_merge($payload, [
        'iat' => $issuedAt,
        'exp' => $issuedAt + $ttlSeconds,
    ]);

    $encodedHeader = base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
    $encodedPayload = base64UrlEncode(json_encode($tokenPayload, JSON_UNESCAPED_SLASHES));
    $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, tokenSecret(), true);

    return $encodedHeader . '.' . $encodedPayload . '.' . base64UrlEncode($signature);
}

function verifyToken(string $token): ?array
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return null;
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
    $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, tokenSecret(), true);

    if (!hash_equals($expectedSignature, base64UrlDecode($encodedSignature))) {
        return null;
    }

    $payload = json_decode(base64UrlDecode($encodedPayload), true);

    if (!is_array($payload)) {
        return null;
    }

    if (!isset($payload['exp']) || (int) $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}
