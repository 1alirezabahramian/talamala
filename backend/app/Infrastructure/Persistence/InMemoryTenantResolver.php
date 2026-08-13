<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence;

use Talamala\Domain\Tenant\Tenant;
use Talamala\Domain\Tenant\TenantResolver;
use Talamala\Domain\Tenant\TenantResolutionException;

/**
 * Stage 1 test/dev implementation. Production uses DB-backed resolver.
 */
final class InMemoryTenantResolver implements TenantResolver
{
    /** @var array<string, Tenant> host => tenant */
    private array $byHost = [];

    public function register(Tenant $tenant): void
    {
        $this->byHost[strtolower($tenant->primaryHost)] = $tenant;
        foreach ($tenant->allowedHosts as $h) {
            $this->byHost[strtolower($h)] = $tenant;
        }
    }

    public function resolveFromHost(string $host): Tenant
    {
        $host = strtolower(trim(explode(':', $host)[0]));
        $tenant = $this->byHost[$host] ?? null;
        if ($tenant === null) {
            throw TenantResolutionException::unknownHost($host);
        }
        if (!$tenant->isActive || !$tenant->isVerified) {
            throw TenantResolutionException::inactiveTenant($tenant->id);
        }
        return $tenant;
    }
}
