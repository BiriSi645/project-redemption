<?php

namespace App\Libraries;

class RealtimeSessionAuthenticator
{
    public function __construct(private readonly string $sessionPath, private readonly string $cookieName = 'ci_session', private readonly int $expiration = 7200) {}

    public function authenticate(string $sessionId): ?array
    {
        if (! preg_match('/^[a-zA-Z0-9,-]{22,128}$/', $sessionId)) return null;
        $path = rtrim($this->sessionPath, '/\\') . DIRECTORY_SEPARATOR . $this->cookieName . $sessionId;
        if (! is_file($path) || filemtime($path) < time() - $this->expiration) return null;
        $handle = @fopen($path, 'rb');
        if ($handle === false) return null;
        try {
            if (! flock($handle, LOCK_SH)) return null;
            $encoded = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally { fclose($handle); }
        if (! is_string($encoded) || $encoded === '') return null;
        session_id(bin2hex(random_bytes(16)));
        if (! @session_start(['use_cookies' => false, 'use_strict_mode' => false])) return null;
        $_SESSION = [];
        $decoded = @session_decode($encoded);
        $session = $_SESSION;
        session_abort();
        session_id('');
        if (! $decoded || empty($session['logged_in']) || (int) ($session['user_id'] ?? 0) < 1) return null;
        return ['userId' => (int) $session['user_id'], 'username' => (string) ($session['username'] ?? 'Kullanıcı')];
    }
}
