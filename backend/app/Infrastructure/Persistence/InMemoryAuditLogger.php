<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence;

use Talamala\Domain\Audit\AuditEvent;
use Talamala\Domain\Audit\AuditLogger;

final class InMemoryAuditLogger implements AuditLogger
{
    /** @var list<AuditEvent> */
    private array $events = [];

    public function log(AuditEvent $event): void
    {
        $this->events[] = $event;
    }

    /** @return list<AuditEvent> */
    public function all(): array
    {
        return $this->events;
    }
}
