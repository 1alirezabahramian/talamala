<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Security;

/**
 * Fixed-window rate limiter contract (OTP etc.).
 * Implementations must be key-scoped; callers include tenant in the key.
 */
interface RateLimiter
{
    /**
     * @return array{allowed:bool, remaining:int, retry_after:int}
     */
    public function hit(string $key): array;
}
