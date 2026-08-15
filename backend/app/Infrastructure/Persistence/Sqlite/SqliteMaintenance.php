<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;

/**
 * Non-financial maintenance: purge expired sessions / idempotency / rate-limit buckets.
 */
final class SqliteMaintenance
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * @return array{sessions:int, idempotency:int, rate_limits:int}
     */
    public function purgeExpired(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $atom = $now->format(\DateTimeInterface::ATOM);
        $ts = $now->getTimestamp();

        $s = $this->pdo->prepare('DELETE FROM sessions WHERE expires_at <= :now');
        $s->execute(['now' => $atom]);

        $i = $this->pdo->prepare('DELETE FROM idempotency_keys WHERE expires_at <= :now');
        $i->execute(['now' => $atom]);

        $r = $this->pdo->prepare('DELETE FROM rate_limits WHERE reset_at <= :now');
        $r->execute(['now' => $ts]);

        return [
            'sessions' => $s->rowCount(),
            'idempotency' => $i->rowCount(),
            'rate_limits' => $r->rowCount(),
        ];
    }
}
