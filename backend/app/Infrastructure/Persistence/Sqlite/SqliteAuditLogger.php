<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;
use Talamala\Domain\Audit\AuditEvent;
use Talamala\Domain\Audit\AuditLogger;

/**
 * Persistence-2: durable audit events (append-only).
 */
final class SqliteAuditLogger implements AuditLogger
{
    public function __construct(private readonly PDO $pdo) {}

    public function log(AuditEvent $event): void
    {
        $st = $this->pdo->prepare(<<<'SQL'
INSERT INTO audit_events (
    id, tenant_id, actor_id, actor_type, action,
    target_type, target_id, reason, correlation_id, metadata_json, occurred_at
) VALUES (
    :id, :tenant_id, :actor_id, :actor_type, :action,
    :target_type, :target_id, :reason, :correlation_id, :metadata_json, :occurred_at
)
SQL);
        $st->execute([
            'id' => $event->id,
            'tenant_id' => $event->tenantId,
            'actor_id' => $event->actorId,
            'actor_type' => $event->actorType,
            'action' => $event->action,
            'target_type' => $event->targetType,
            'target_id' => $event->targetId,
            'reason' => $event->reason,
            'correlation_id' => $event->correlationId,
            'metadata_json' => json_encode($event->metadata, JSON_UNESCAPED_UNICODE),
            'occurred_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Test/helper: list events for a tenant (newest first).
     * @return list<AuditEvent>
     */
    public function listForTenant(string $tenantId, int $limit = 100): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM audit_events WHERE tenant_id = :t ORDER BY occurred_at DESC LIMIT :lim'
        );
        $st->bindValue('t', $tenantId);
        $st->bindValue('lim', $limit, PDO::PARAM_INT);
        $st->execute();
        $out = [];
        while ($row = $st->fetch()) {
            $meta = json_decode($row['metadata_json'] ?? '{}', true);
            if (!is_array($meta)) {
                $meta = [];
            }
            $out[] = new AuditEvent(
                id: $row['id'],
                tenantId: $row['tenant_id'],
                actorId: $row['actor_id'],
                actorType: $row['actor_type'],
                action: $row['action'],
                targetType: $row['target_type'],
                targetId: $row['target_id'],
                reason: $row['reason'],
                correlationId: $row['correlation_id'],
                metadata: $meta,
                occurredAt: new \DateTimeImmutable($row['occurred_at'], new \DateTimeZone('UTC')),
            );
        }
        return $out;
    }
}
