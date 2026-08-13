<?php

declare(strict_types=1);

namespace Talamala\Domain\Session;

interface SessionStore
{
    public function put(SessionRecord $session): void;

    public function get(string $token): ?SessionRecord;

    public function revoke(string $token): void;
}
