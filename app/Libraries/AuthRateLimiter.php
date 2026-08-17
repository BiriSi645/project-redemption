<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;

class AuthRateLimiter
{
    public function __construct(private readonly CacheInterface $cache, private readonly int $windowSeconds = 300)
    {
    }

    public function hit(string $action, string $ipAddress, int $maximumAttempts): bool
    {
        $key = $this->key($action, $ipAddress);
        $attempts = (int) ($this->cache->get($key) ?: 0) + 1;
        $this->cache->save($key, $attempts, $this->windowSeconds);

        return $attempts > $maximumAttempts;
    }

    public function clear(string $action, string $ipAddress): void
    {
        $this->cache->delete($this->key($action, $ipAddress));
    }

    private function key(string $action, string $ipAddress): string
    {
        return 'auth_rate_' . hash('sha256', $action . '|' . $ipAddress);
    }
}
