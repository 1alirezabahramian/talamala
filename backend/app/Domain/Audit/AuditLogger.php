<?php

declare(strict_types=1);

namespace Talamala\Domain\Audit;

interface AuditLogger
{
    public function log(AuditEvent $event): void;
}
