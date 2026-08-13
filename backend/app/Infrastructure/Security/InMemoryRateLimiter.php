<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Security;

/**
 * Process-local fixed window rate limiter (skeleton).
 * Production should use Redis/shared store per tenant+key.
 */
final class InMemoryRateLimiter
{
    /** @var array<string, array{count:int, reset_at:int}> */
    private array $buckets = [];

    public function __construct(
        private readonly int $maxAttempts = 5,
        private readonly int $windowSeconds = 300,
    ) {}

    /**
     * @return array{allowed:bool, remaining:int, retry_after:int}
     */
    public function hit(string $key): array
    {
        $now = time();
        $bucket = $this->buckets[$key] ?? null;
        if ($bucket === null || $bucket['reset_at'] <= $now) {
            $this->buckets[$key] = ['count' => 1, 'reset_at' => $now + $this->windowSeconds];
            return [
                'allowed' => true,
                'remaining' => $this->maxAttempts - 1,
                'retry_after' => 0,
            ];
        }
        if ($bucket['count'] >= $this->maxAttempts) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => max(0, $bucket['reset_at'] - $now),
            ];
        }
        $this->buckets[$key]['count']++;
        return [
            'allowed' => true,
            'remaining' => $this->maxAttempts - $this->buckets[$key]['count'],
            'retry_after' => 0,
        ];
    }
}
