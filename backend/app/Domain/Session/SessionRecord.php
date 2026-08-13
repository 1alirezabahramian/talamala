<?php

declare(strict_types=1);

namespace Talamala\Domain\Session;

final class SessionRecord
{
    public function __construct(
        public readonly string $token,
        public readonly string $tenantId,
        public readonly string $subjectType, // customer|staff
        public readonly string $subjectId,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly array $meta = [],
    ) {}

    public function isValid(\DateTimeImmutable $now): bool
    {
        return $now < $this->expiresAt;
    }
}
