<?php

declare(strict_types=1);

return new class {
    public function up(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS audit_logs (
    id              UUID PRIMARY KEY,
    tenant_id       UUID NOT NULL,
    actor_id        UUID NULL,
    actor_type      VARCHAR(32) NOT NULL,
    action          VARCHAR(128) NOT NULL,
    target_type     VARCHAR(64) NULL,
    target_id       VARCHAR(64) NULL,
    reason          TEXT NULL,
    correlation_id  VARCHAR(64) NOT NULL,
    metadata        JSONB NOT NULL DEFAULT '{}',
    occurred_at     TIMESTAMPTZ NOT NULL
);

CREATE INDEX idx_audit_tenant_time ON audit_logs(tenant_id, occurred_at DESC);
CREATE INDEX idx_audit_correlation ON audit_logs(correlation_id);

CREATE TABLE IF NOT EXISTS idempotency_registry (
    id              UUID PRIMARY KEY,
    tenant_id       UUID NOT NULL,
    scope           VARCHAR(64) NOT NULL,
    key             VARCHAR(128) NOT NULL,
    result          JSONB NOT NULL,
    expires_at      TIMESTAMPTZ NOT NULL,
    created_at      TIMESTAMPTZ NOT NULL,
    UNIQUE (tenant_id, scope, key)
);
SQL;
    }
};
