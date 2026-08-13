<?php

declare(strict_types=1);

namespace Talamala\Domain\Idempotency;

/**
 * Idempotency is always tenant-scoped.
 */
final class IdempotencyKey
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $key,
        public readonly string $scope, // e.g. order.create, otp.verify
    ) {
        if ($tenantId === '' || $key === '') {
            throw new \InvalidArgumentException('tenantId and key are required');
        }
    }

    public function composite(): string
    {
        return $this->tenantId . ':' . $this->scope . ':' . $this->key;
    }
}
