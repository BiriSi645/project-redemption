<?php

namespace App\Libraries;

interface AuthRateLimitStore
{
    public function increment(string $key, int $windowSeconds): int;

    public function delete(string $key): void;
}
