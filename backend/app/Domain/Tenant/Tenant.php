<?php

declare(strict_types=1);

namespace Talamala\Domain\Tenant;

/**
 * Tenant is resolved exclusively from verified Host/domain.
 * Client-supplied tenant_id is never authoritative.
 */
final class Tenant
{
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $primaryHost,
        public readonly bool $isActive,
        public readonly bool $isVerified,
        public readonly array $allowedHosts = [],
    ) {}

    public function allowsHost(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === strtolower($this->primaryHost)) {
            return true;
        }
        foreach ($this->allowedHosts as $allowed) {
            if ($host === strtolower($allowed)) {
                return true;
            }
        }
        return false;
    }
}
