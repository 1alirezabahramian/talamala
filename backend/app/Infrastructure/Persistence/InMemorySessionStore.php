<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence;

use Talamala\Domain\Session\SessionRecord;
use Talamala\Domain\Session\SessionStore;

final class InMemorySessionStore implements SessionStore
{
    /** @var array<string, SessionRecord> */
    private array $sessions = [];

    public function put(SessionRecord $session): void
    {
        $this->sessions[$session->token] = $session;
    }

    public function get(string $token): ?SessionRecord
    {
        return $this->sessions[$token] ?? null;
    }

    public function revoke(string $token): void
    {
        unset($this->sessions[$token]);
    }

    public function purgeExpired(\DateTimeImmutable $now): int
    {
        $n = 0;
        foreach ($this->sessions as $token => $session) {
            if (!$session->isValid($now)) {
                unset($this->sessions[$token]);
                $n++;
            }
        }
        return $n;
    }
}
