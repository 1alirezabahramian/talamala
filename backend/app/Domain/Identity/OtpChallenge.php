<?php

declare(strict_types=1);

namespace Talamala\Domain\Identity;

/**
 * Short-lived secure verification state. Never store plain OTP long-term.
 * Rate limiting must be applied at application boundary.
 */
final class OtpChallenge
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $mobile,
        public readonly string $purpose, // login | registration
        public readonly string $codeHash,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly int $attempts,
        public readonly int $maxAttempts,
    ) {}

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= $this->maxAttempts;
    }
}
