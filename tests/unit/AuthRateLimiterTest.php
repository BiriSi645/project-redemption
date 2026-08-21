<?php

use App\Libraries\AuthRateLimiter;
use App\Libraries\AuthRateLimitStore;
use CodeIgniter\Test\CIUnitTestCase;

final class AuthRateLimiterTest extends CIUnitTestCase
{
    public function testBlocksOnlyAfterMaximumAttemptIsExceeded(): void
    {
        [$limiter] = $this->limiter();

        $this->assertFalse($limiter->hit('login:user', '127.0.0.1', 2));
        $this->assertFalse($limiter->hit('login:user', '127.0.0.1', 2));
        $this->assertTrue($limiter->hit('login:user', '127.0.0.1', 2));
    }

    public function testSeparatesActionsAndIpAddresses(): void
    {
        [$limiter] = $this->limiter();

        $this->assertFalse($limiter->hit('login:a', '127.0.0.1', 1));
        $this->assertTrue($limiter->hit('login:a', '127.0.0.1', 1));
        $this->assertFalse($limiter->hit('login:b', '127.0.0.1', 1));
        $this->assertFalse($limiter->hit('login:a', '127.0.0.2', 1));
    }

    public function testClearResetsAttemptCounter(): void
    {
        [$limiter] = $this->limiter();
        $this->assertFalse($limiter->hit('login:user', '127.0.0.1', 1));
        $this->assertTrue($limiter->hit('login:user', '127.0.0.1', 1));

        $limiter->clear('login:user', '127.0.0.1');

        $this->assertFalse($limiter->hit('login:user', '127.0.0.1', 1));
    }

    private function limiter(): array
    {
        $store = new class implements AuthRateLimitStore {
            public array $values = [];

            public function increment(string $key, int $windowSeconds): int
            {
                return $this->values[$key] = ($this->values[$key] ?? 0) + 1;
            }

            public function delete(string $key): void
            {
                unset($this->values[$key]);
            }
        };

        return [new AuthRateLimiter($store, 300), &$store->values];
    }
}
