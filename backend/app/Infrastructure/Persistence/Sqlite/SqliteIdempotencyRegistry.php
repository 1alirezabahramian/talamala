<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;
use Talamala\Domain\Idempotency\IdempotencyKey;
use Talamala\Domain\Idempotency\IdempotencyRegistry;

/**
 * Persistence-2: tenant-scoped durable idempotency keys.
 */
final class SqliteIdempotencyRegistry implements IdempotencyRegistry
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(IdempotencyKey $key): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT result_json, expires_at FROM idempotency_keys
             WHERE tenant_id = :t AND scope = :s AND key = :k LIMIT 1'
        );
        $st->execute([
            't' => $key->tenantId,
            's' => $key->scope,
            'k' => $key->key,
        ]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $expires = new \DateTimeImmutable($row['expires_at'], new \DateTimeZone('UTC'));
        if ($expires < new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) {
            $del = $this->pdo->prepare(
                'DELETE FROM idempotency_keys WHERE tenant_id = :t AND scope = :s AND key = :k'
            );
            $del->execute(['t' => $key->tenantId, 's' => $key->scope, 'k' => $key->key]);
            return null;
        }
        $result = json_decode($row['result_json'], true);
        return is_array($result) ? $result : null;
    }

    public function store(IdempotencyKey $key, array $result, \DateTimeImmutable $expiresAt): void
    {
        $st = $this->pdo->prepare(<<<'SQL'
INSERT INTO idempotency_keys (tenant_id, scope, key, result_json, expires_at)
VALUES (:t, :s, :k, :r, :e)
ON CONFLICT(tenant_id, scope, key) DO UPDATE SET
    result_json = excluded.result_json,
    expires_at = excluded.expires_at
SQL);
        $st->execute([
            't' => $key->tenantId,
            's' => $key->scope,
            'k' => $key->key,
            'r' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'e' => $expiresAt->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function purgeExpired(\DateTimeImmutable $now): int
    {
        $st = $this->pdo->prepare('DELETE FROM idempotency_keys WHERE expires_at <= :now');
        $st->execute(['now' => $now->format(\DateTimeInterface::ATOM)]);
        return $st->rowCount();
    }
}
