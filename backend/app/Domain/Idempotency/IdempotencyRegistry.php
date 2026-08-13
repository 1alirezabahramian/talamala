<?php

declare(strict_types=1);

namespace Talamala\Domain\Idempotency;

interface IdempotencyRegistry
{
    /**
     * Returns previous result if key already processed, null otherwise.
     * Must be atomic and tenant-scoped.
     */
    public function find(IdempotencyKey $key): ?array;

    /**
     * Store successful result for the key. Call only after side-effect succeeded.
     */
    public function store(IdempotencyKey $key, array $result, \DateTimeImmutable $expiresAt): void;
}
