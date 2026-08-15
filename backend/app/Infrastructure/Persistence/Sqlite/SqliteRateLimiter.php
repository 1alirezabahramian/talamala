<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;
use Talamala\Infrastructure\Security\RateLimiter;

/**
 * Durable fixed-window rate limiter (OTP).
 * Window parameters match existing skeleton: 5 / 300s (OpenAPI + Kernel).
 */
final class SqliteRateLimiter implements RateLimiter
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly int $maxAttempts = 5,
        private readonly int $windowSeconds = 300,
    ) {}

    public function hit(string $key): array
    {
        $now = time();
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare(
                'SELECT hit_count, reset_at FROM rate_limits WHERE bucket_key = :k LIMIT 1'
            );
            $st->execute(['k' => $key]);
            $row = $st->fetch();

            if ($row === false || (int) $row['reset_at'] <= $now) {
                $resetAt = $now + $this->windowSeconds;
                $up = $this->pdo->prepare(<<<'SQL'
INSERT INTO rate_limits (bucket_key, hit_count, reset_at)
VALUES (:k, 1, :r)
ON CONFLICT(bucket_key) DO UPDATE SET hit_count = 1, reset_at = excluded.reset_at
SQL);
                $up->execute(['k' => $key, 'r' => $resetAt]);
                $this->pdo->commit();
                return [
                    'allowed' => true,
                    'remaining' => $this->maxAttempts - 1,
                    'retry_after' => 0,
                ];
            }

            $count = (int) $row['hit_count'];
            $resetAt = (int) $row['reset_at'];
            if ($count >= $this->maxAttempts) {
                $this->pdo->commit();
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'retry_after' => max(0, $resetAt - $now),
                ];
            }

            $up = $this->pdo->prepare(
                'UPDATE rate_limits SET hit_count = hit_count + 1 WHERE bucket_key = :k'
            );
            $up->execute(['k' => $key]);
            $this->pdo->commit();
            return [
                'allowed' => true,
                'remaining' => $this->maxAttempts - ($count + 1),
                'retry_after' => 0,
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function purgeExpired(): int
    {
        $now = time();
        $st = $this->pdo->prepare('DELETE FROM rate_limits WHERE reset_at <= :now');
        $st->execute(['now' => $now]);
        return $st->rowCount();
    }
}
