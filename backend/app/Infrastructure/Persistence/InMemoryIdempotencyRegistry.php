<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence;

use Talamala\Domain\Idempotency\IdempotencyKey;
use Talamala\Domain\Idempotency\IdempotencyRegistry;

final class InMemoryIdempotencyRegistry implements IdempotencyRegistry
{
    /** @var array<string, array{result: array, expires: \DateTimeImmutable}> */
    private array $store = [];

    public function find(IdempotencyKey $key): ?array
    {
        $k = $key->composite();
        if (!isset($this->store[$k])) {
            return null;
        }
        if ($this->store[$k]['expires'] < new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) {
            unset($this->store[$k]);
            return null;
        }
        return $this->store[$k]['result'];
    }

    public function store(IdempotencyKey $key, array $result, \DateTimeImmutable $expiresAt): void
    {
        $this->store[$key->composite()] = [
            'result' => $result,
            'expires' => $expiresAt,
        ];
    }
}
