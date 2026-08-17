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
            'version' => self::readVersion(),
            'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
        ];
    }

    public function ready(): array
    {
        // Future: check DB, cache, Kimia connectivity (without leaking credentials).
        return [
            'status' => 'ready',
            'version' => self::readVersion(),
            'checks' => [
                'app' => 'ok',
            ],
        ];
    }

    private static function readVersion(): string
    {
        $candidates = [
            dirname(__DIR__, 4) . '/VERSION', // repo root from backend/app/Http/Controllers
            dirname(__DIR__, 3) . '/VERSION',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $v = trim((string) file_get_contents($path));
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return 'dev';
    }
}
