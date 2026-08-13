<?php

declare(strict_types=1);

return new class {
    public function up(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS quotes (
    id                  UUID PRIMARY KEY,
    tenant_id           UUID NOT NULL,
    customer_id         UUID NOT NULL,
    side                VARCHAR(8) NOT NULL,
    asset               VARCHAR(32) NOT NULL,
    quantity            NUMERIC(24, 8) NOT NULL,
    unit_price_rial     NUMERIC(24, 0) NOT NULL,
    total_rial          NUMERIC(24, 0) NOT NULL,
    issued_at           TIMESTAMPTZ NOT NULL,
    expires_at          TIMESTAMPTZ NOT NULL,
    status              VARCHAR(16) NOT NULL,
    price_source_ref    VARCHAR(128) NULL,
    metadata            JSONB NOT NULL DEFAULT '{}'
);
CREATE INDEX idx_quotes_tenant_customer ON quotes(tenant_id, customer_id);

CREATE TABLE IF NOT EXISTS custody_items (
    id                  UUID PRIMARY KEY,
    tenant_id           UUID NOT NULL,
    customer_id         UUID NOT NULL,
    description         VARCHAR(500) NOT NULL,
    weight_grams        NUMERIC(18, 6) NOT NULL,
    fineness            VARCHAR(16) NULL,
    status              VARCHAR(32) NOT NULL,
    received_at         TIMESTAMPTZ NOT NULL,
    ready_at            TIMESTAMPTZ NULL,
    delivered_at        TIMESTAMPTZ NULL,
    barcode_ref         VARCHAR(128) NULL,
    notes               TEXT NULL
);
CREATE INDEX idx_custody_tenant_customer ON custody_items(tenant_id, customer_id);
CREATE INDEX idx_custody_status ON custody_items(tenant_id, status);
SQL;
    }
};
