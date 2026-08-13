<?php

declare(strict_types=1);

return new class {
    public function up(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS orders (
    id                  UUID PRIMARY KEY,
    tenant_id           UUID NOT NULL,
    customer_id         UUID NOT NULL,
    quote_id            UUID NOT NULL,
    side                VARCHAR(8) NOT NULL,
    asset               VARCHAR(32) NOT NULL,
    quantity            NUMERIC(24, 8) NOT NULL,
    unit_price_rial     NUMERIC(24, 0) NOT NULL,
    total_rial          NUMERIC(24, 0) NOT NULL,
    status              VARCHAR(32) NOT NULL,
    idempotency_key     VARCHAR(128) NULL,
    kimia_record_id     VARCHAR(64) NULL,
    failure_reason      TEXT NULL,
    created_at          TIMESTAMPTZ NOT NULL,
    UNIQUE (tenant_id, idempotency_key)
);
CREATE INDEX idx_orders_tenant_customer ON orders(tenant_id, customer_id);
CREATE INDEX idx_orders_quote ON orders(tenant_id, quote_id);
SQL;
    }
};
