<?php

declare(strict_types=1);

namespace Talamala\Domain\Tenant;

/**
 * ADR-001: Host-based tenant resolution, fail-closed.
 * Unknown / inactive / unverified hosts must not proceed.
 */
interface TenantResolver
{
    /**
     * Resolve tenant from HTTP Host header (or equivalent).
     * Throws TenantResolutionException on failure (fail-closed).
     */
    public function resolveFromHost(string $host): Tenant;
}
