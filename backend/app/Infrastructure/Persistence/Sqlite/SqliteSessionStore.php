<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;
use Talamala\Domain\Session\SessionRecord;
use Talamala\Domain\Session\SessionStore;

/**
 * Persistence-2: durable sessions (tenant-scoped via record fields).
 */
final class SqliteSessionStore implements SessionStore
{
    public function __construct(private readonly PDO $pdo) {}

    public function put(SessionRecord $session): void
    {
        $st = $this->pdo->prepare(<<<'SQL'
INSERT INTO sessions (token, tenant_id, subject_type, subject_id, expires_at, meta_json)
VALUES (:token, :tenant_id, :subject_type, :subject_id, :expires_at, :meta_json)
ON CONFLICT(token) DO UPDATE SET
    tenant_id = excluded.tenant_id,
    subject_type = excluded.subject_type,
    subject_id = excluded.subject_id,
    expires_at = excluded.expires_at,
    meta_json = excluded.meta_json
SQL);
        $st->execute([
            'token' => $session->token,
            'tenant_id' => $session->tenantId,
            'subject_type' => $session->subjectType,
            'subject_id' => $session->subjectId,
            'expires_at' => $session->expiresAt->format(\DateTimeInterface::ATOM),
            'meta_json' => json_encode($session->meta, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function get(string $token): ?SessionRecord
    {
        $st = $this->pdo->prepare('SELECT * FROM sessions WHERE token = :t LIMIT 1');
        $st->execute(['t' => $token]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }
        $expires = new \DateTimeImmutable($row['expires_at'], new \DateTimeZone('UTC'));
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($now >= $expires) {
            $this->revoke($token);
            return null;
        }
        $meta = json_decode($row['meta_json'] ?? '{}', true);
        if (!is_array($meta)) {
            $meta = [];
        }
        return new SessionRecord(
            token: $row['token'],
            tenantId: $row['tenant_id'],
            subjectType: $row['subject_type'],
            subjectId: $row['subject_id'],
            expiresAt: $expires,
            meta: $meta,
        );
    }

    public function revoke(string $token): void
    {
        $st = $this->pdo->prepare('DELETE FROM sessions WHERE token = :t');
        $st->execute(['t' => $token]);
    }
}
