<?php

namespace App\Libraries;

class AuthRateLimiter
{
    public function __construct(private readonly AuthRateLimitStore $store, private readonly int $windowSeconds = 300)
    {
    }

    public function hit(string $action, string $ipAddress, int $maximumAttempts): bool
    {
        $key = $this->key($action, $ipAddress);
        $attempts = $this->store->increment($key, $this->windowSeconds);

        return $attempts > $maximumAttempts;
    }

    public function clear(string $action, string $ipAddress): void
    {
        $this->store->delete($this->key($action, $ipAddress));
    }

    private function key(string $action, string $ipAddress): string
    {
        return 'auth_rate_' . hash('sha256', $action . '|' . $ipAddress);
    }
}
