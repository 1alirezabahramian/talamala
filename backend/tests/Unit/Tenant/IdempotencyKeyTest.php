<?php

declare(strict_types=1);

namespace Talamala\Tests\Unit\Tenant;

use PHPUnit\Framework\TestCase;
use Talamala\Domain\Idempotency\IdempotencyKey;

final class IdempotencyKeyTest extends TestCase
{
    public function testCompositeIsTenantScoped(): void
    {
        $key = new IdempotencyKey('tenant-a', 'abc-123', 'order.create');
        $this->assertSame('tenant-a:order.create:abc-123', $key->composite());
    }

    public function testRejectsEmptyTenant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IdempotencyKey('', 'k', 'scope');
    }
}
