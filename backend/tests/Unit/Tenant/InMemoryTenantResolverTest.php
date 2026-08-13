<?php

declare(strict_types=1);

namespace Talamala\Tests\Unit\Tenant;

use PHPUnit\Framework\TestCase;
use Talamala\Domain\Tenant\Tenant;
use Talamala\Domain\Tenant\TenantResolutionException;
use Talamala\Infrastructure\Persistence\InMemoryTenantResolver;

final class InMemoryTenantResolverTest extends TestCase
{
    public function testResolvesActiveVerifiedHost(): void
    {
        $resolver = new InMemoryTenantResolver();
        $tenant = new Tenant('t1', 'demo', 'demo.talamala.local', true, true);
        $resolver->register($tenant);

        $resolved = $resolver->resolveFromHost('demo.talamala.local');
        $this->assertSame('t1', $resolved->id);
    }

    public function testUnknownHostFailsClosed(): void
    {
        $resolver = new InMemoryTenantResolver();
        $this->expectException(TenantResolutionException::class);
        $resolver->resolveFromHost('unknown.example');
    }

    public function testInactiveTenantFailsClosed(): void
    {
        $resolver = new InMemoryTenantResolver();
        $resolver->register(new Tenant('t2', 'off', 'off.local', false, true));
        $this->expectException(TenantResolutionException::class);
        $resolver->resolveFromHost('off.local');
    }
}
