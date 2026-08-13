<?php

declare(strict_types=1);

namespace Talamala\Domain\Audit;

/**
 * Mandatory fields: actor + tenant + target + reason + correlation.
 */
final class AuditEvent
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $actorId,
        public readonly string $actorType, // customer|staff|system
        public readonly string $action,
        public readonly ?string $targetType,
        public readonly ?string $targetId,
        public readonly ?string $reason,
        public readonly string $correlationId,
        public readonly array $metadata,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}
