<?php

use App\Libraries\AuthRateLimiter;
use CodeIgniter\Cache\CacheInterface;
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
        $values = [];
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(static function (string $key) use (&$values): mixed {
            return $values[$key] ?? null;
        });
        $cache->method('save')->willReturnCallback(static function (string $key, mixed $value, int $ttl) use (&$values): bool {
            $values[$key] = $value;
            return true;
        });
        $cache->method('delete')->willReturnCallback(static function (string $key) use (&$values): bool {
            unset($values[$key]);
            return true;
        });

        return [new AuthRateLimiter($cache, 300), &$values];
    }
}
