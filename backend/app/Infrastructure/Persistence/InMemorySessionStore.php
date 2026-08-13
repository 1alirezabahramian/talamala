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
}
