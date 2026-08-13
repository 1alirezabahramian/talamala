<?php

declare(strict_types=1);

namespace Talamala\Domain\Tenant;

final class TenantResolutionException extends \RuntimeException
{
    public static function unknownHost(string $host): self
    {
        return new self(sprintf('Unknown or unverified host: %s', $host), 404);
    }

    public static function inactiveTenant(string $tenantId): self
    {
        return new self(sprintf('Tenant is inactive: %s', $tenantId), 403);
    }
}
