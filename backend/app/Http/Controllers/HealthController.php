<?php

declare(strict_types=1);

namespace Talamala\Http\Controllers;

/**
 * Stage 1 health / readiness.
 * No secrets, no tenant data leakage.
 */
final class HealthController
{
    public function live(): array
    {
        return [
            'status' => 'ok',
            'service' => 'talamala-backend',
            'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
        ];
    }

    public function ready(): array
    {
        // Future: check DB, cache, Kimia connectivity (without leaking credentials).
        return [
            'status' => 'ready',
            'checks' => [
                'app' => 'ok',
            ],
        ];
    }
}
