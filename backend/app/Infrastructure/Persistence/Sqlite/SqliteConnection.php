<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;

/**
 * Stage Persistence-1: SQLite via PDO (no Composer).
 * Path from TALAMALA_DB_PATH; default :memory: for isolated smoke.
 * Schema mirrors conceptual migrations (SQLite dialect).
 */
final class SqliteConnection
{
    private static ?PDO $sharedMemory = null;

    public static function fromEnv(): PDO
    {
        $path = getenv('TALAMALA_DB_PATH') ?: ':memory:';
        return self::connect($path);
    }

    public static function connect(string $path): PDO
    {
        // Share one :memory: DB per process so multiple repos see same data
        if ($path === ':memory:') {
            if (self::$sharedMemory === null) {
                self::$sharedMemory = self::createPdo($path);
                self::migrate(self::$sharedMemory);
            }
            return self::$sharedMemory;
        }

        $dir = dirname($path);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = self::createPdo($path);
        self::migrate($pdo);
        return $pdo;
    }

    private static function createPdo(string $path): PDO
    {
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }

    public static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS customers (
    id               TEXT PRIMARY KEY,
    tenant_id        TEXT NOT NULL,
    mobile           TEXT NOT NULL,
    national_code    TEXT NULL,
    full_name        TEXT NULL,
    access_status    TEXT NOT NULL DEFAULT 'limited',
    kimia_account_id INTEGER NULL,
    created_at       TEXT NOT NULL,
    approved_at      TEXT NULL,
    UNIQUE (tenant_id, mobile)
);
CREATE INDEX IF NOT EXISTS idx_customers_tenant ON customers(tenant_id);
CREATE INDEX IF NOT EXISTS idx_customers_status ON customers(tenant_id, access_status);

CREATE TABLE IF NOT EXISTS quotes (
    id               TEXT PRIMARY KEY,
    tenant_id        TEXT NOT NULL,
    customer_id      TEXT NOT NULL,
    side             TEXT NOT NULL,
    asset            TEXT NOT NULL,
    quantity         TEXT NOT NULL,
    unit_price_rial  TEXT NOT NULL,
    total_rial       TEXT NOT NULL,
    issued_at        TEXT NOT NULL,
    expires_at       TEXT NOT NULL,
    status           TEXT NOT NULL,
    price_source_ref TEXT NULL,
    metadata_json    TEXT NOT NULL DEFAULT '{}'
);
CREATE INDEX IF NOT EXISTS idx_quotes_tenant_customer ON quotes(tenant_id, customer_id);

CREATE TABLE IF NOT EXISTS custody_items (
    id               TEXT PRIMARY KEY,
    tenant_id        TEXT NOT NULL,
    customer_id      TEXT NOT NULL,
    description      TEXT NOT NULL,
    weight_grams     TEXT NOT NULL,
    fineness         TEXT NULL,
    status           TEXT NOT NULL,
    received_at      TEXT NOT NULL,
    ready_at         TEXT NULL,
    delivered_at     TEXT NULL,
    barcode_ref      TEXT NULL,
    notes            TEXT NULL
);
CREATE INDEX IF NOT EXISTS idx_custody_tenant_customer ON custody_items(tenant_id, customer_id);
CREATE INDEX IF NOT EXISTS idx_custody_status ON custody_items(tenant_id, status);

CREATE TABLE IF NOT EXISTS orders (
    id               TEXT PRIMARY KEY,
    tenant_id        TEXT NOT NULL,
    customer_id      TEXT NOT NULL,
    quote_id         TEXT NOT NULL,
    side             TEXT NOT NULL,
    asset            TEXT NOT NULL,
    quantity         TEXT NOT NULL,
    unit_price_rial  TEXT NOT NULL,
    total_rial       TEXT NOT NULL,
    status           TEXT NOT NULL,
    idempotency_key  TEXT NULL,
    kimia_record_id  TEXT NULL,
    failure_reason   TEXT NULL,
    created_at       TEXT NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_orders_tenant_idem ON orders(tenant_id, idempotency_key)
    WHERE idempotency_key IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_orders_tenant_customer ON orders(tenant_id, customer_id);
CREATE INDEX IF NOT EXISTS idx_orders_quote ON orders(tenant_id, quote_id);
SQL);
    }
}
