<?php

declare(strict_types=1);

/**
 * Conceptual migration — wire with Laravel migrator when full app bootstrap lands.
 * tenants + tenant_domains enforce Host-based resolution.
 */

return new class {
    public function up(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS tenants (
    id              UUID PRIMARY KEY,
    slug            VARCHAR(64) NOT NULL UNIQUE,
    name            VARCHAR(255) NOT NULL,
    is_active       BOOLEAN NOT NULL DEFAULT true,
    is_verified     BOOLEAN NOT NULL DEFAULT false,
    created_at      TIMESTAMPTZ NOT NULL,
    updated_at      TIMESTAMPTZ NOT NULL
);

CREATE TABLE IF NOT EXISTS tenant_domains (
    id              UUID PRIMARY KEY,
    tenant_id       UUID NOT NULL REFERENCES tenants(id),
    host            VARCHAR(255) NOT NULL UNIQUE,
    is_primary      BOOLEAN NOT NULL DEFAULT false,
    created_at      TIMESTAMPTZ NOT NULL
);

CREATE INDEX idx_tenant_domains_tenant ON tenant_domains(tenant_id);
SQL;
    }
};
