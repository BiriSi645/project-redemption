<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

final class DatabaseAuthRateLimitStore implements AuthRateLimitStore
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function increment(string $key, int $windowSeconds): int
    {
        $this->db->query(
            'INSERT INTO auth_rate_limits (rate_key, attempts, expires_at, created_at, updated_at)
             VALUES (?, 1, DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND), UTC_TIMESTAMP(), UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
                attempts = IF(expires_at <= UTC_TIMESTAMP(), 1, attempts + 1),
                expires_at = IF(expires_at <= UTC_TIMESTAMP(), VALUES(expires_at), expires_at),
                updated_at = UTC_TIMESTAMP()',
            [$key, max(1, $windowSeconds)]
        );

        $row = $this->db->table('auth_rate_limits')
            ->select('attempts')
            ->where('rate_key', $key)
            ->get()
            ->getRowArray();

        return (int) ($row['attempts'] ?? 1);
    }

    public function delete(string $key): void
    {
        $this->db->table('auth_rate_limits')->where('rate_key', $key)->delete();
    }
}
