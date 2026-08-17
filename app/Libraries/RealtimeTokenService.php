<?php

namespace App\Libraries;

use RuntimeException;

final class RealtimeTokenService
{
    private const AUDIENCE = 'project-redemption-realtime';

    public function issue(int $userId, string $username, int $ttlSeconds = 120): string
    {
        if ($userId < 1) {
            throw new RuntimeException('Geçersiz realtime kullanıcı kimliği.');
        }

        $now = time();
        $payload = [
            'aud' => self::AUDIENCE,
            'sub' => $userId,
            'name' => $username,
            'iat' => $now,
            'exp' => $now + max(30, min($ttlSeconds, 300)),
            'nonce' => bin2hex(random_bytes(8)),
        ];

        $encodedPayload = $this->base64UrlEncode(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
        $signature = hash_hmac('sha256', $encodedPayload, $this->secret(), true);

        return $encodedPayload . '.' . $this->base64UrlEncode($signature);
    }

    public function verify(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        [$encodedPayload, $encodedSignature] = $parts;
        $signature = $this->base64UrlDecode($encodedSignature);
        if ($signature === null) {
            return null;
        }

        $expected = hash_hmac('sha256', $encodedPayload, $this->secret(), true);
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = $this->base64UrlDecode($encodedPayload);
        if ($decoded === null) {
            return null;
        }

        try {
            $payload = json_decode($decoded, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload)
            || ($payload['aud'] ?? null) !== self::AUDIENCE
            || (int) ($payload['sub'] ?? 0) < 1
            || (int) ($payload['exp'] ?? 0) < time()
            || (int) ($payload['iat'] ?? 0) > time() + 30
        ) {
            return null;
        }

        return [
            'userId' => (int) $payload['sub'],
            'username' => (string) ($payload['name'] ?? 'Kullanıcı'),
            'expiresAt' => (int) $payload['exp'],
        ];
    }

    private function secret(): string
    {
        $secret = (string) (
            getenv('REALTIME_SECRET')
            ?: ($_ENV['REALTIME_SECRET'] ?? $_SERVER['REALTIME_SECRET'] ?? null)
            ?: getenv('realtime.secret')
            ?: ($_ENV['realtime.secret'] ?? $_SERVER['realtime.secret'] ?? '')
        );

        if (strlen($secret) < 32) {
            throw new RuntimeException(
                'REALTIME_SECRET ayarlanmamış veya çok kısa. En az 32 karakterlik güçlü bir secret kullanın.'
            );
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            return null;
        }

        $padding = strlen($value) % 4;
        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
