<?php

declare(strict_types=1);

namespace Talamala\Tests\Feature\Tenant;

use PHPUnit\Framework\TestCase;
use Talamala\Domain\Idempotency\IdempotencyKey;
use Talamala\Domain\Tenant\Tenant;
use Talamala\Domain\Tenant\TenantResolutionException;
use Talamala\Infrastructure\Persistence\InMemoryIdempotencyRegistry;
use Talamala\Infrastructure\Persistence\InMemoryTenantResolver;

final class TenantIsolationTest extends TestCase
{
    public function testIdempotencyKeysDoNotCollideAcrossTenants(): void
    {
        $registry = new InMemoryIdempotencyRegistry();
        $expires = new \DateTimeImmutable('+1 hour');

        $keyA = new IdempotencyKey('tenant-a', 'same-key', 'order.create');
        $keyB = new IdempotencyKey('tenant-b', 'same-key', 'order.create');

        $registry->store($keyA, ['order_id' => 'A1'], $expires);
        $registry->store($keyB, ['order_id' => 'B1'], $expires);

        $this->assertSame('A1', $registry->find($keyA)['order_id']);
        $this->assertSame('B1', $registry->find($keyB)['order_id']);
    }

    public function testHostResolutionFailClosed(): void
    {
        $resolver = new InMemoryTenantResolver();
        $resolver->register(new Tenant('t1', 'shop', 'shop.example', true, true));

        $this->assertSame('t1', $resolver->resolveFromHost('shop.example')->id);

        $this->expectException(TenantResolutionException::class);
        $resolver->resolveFromHost('evil.example');
    }
}
