<?php

declare(strict_types=1);

return new class {
    public function up(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS customers (
    id                  UUID PRIMARY KEY,
    tenant_id           UUID NOT NULL,
    mobile              VARCHAR(20) NOT NULL,
    national_code       VARCHAR(20) NULL,
    full_name           VARCHAR(255) NULL,
    access_status       VARCHAR(32) NOT NULL DEFAULT 'limited',
    kimia_account_id    INTEGER NULL,
    created_at          TIMESTAMPTZ NOT NULL,
    approved_at         TIMESTAMPTZ NULL,
    UNIQUE (tenant_id, mobile)
);

CREATE INDEX idx_customers_tenant ON customers(tenant_id);
CREATE INDEX idx_customers_kimia ON customers(tenant_id, kimia_account_id)
    WHERE kimia_account_id IS NOT NULL;
SQL;
    }
};
