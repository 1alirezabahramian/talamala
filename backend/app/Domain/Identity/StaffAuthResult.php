<?php

declare(strict_types=1);

namespace Talamala\Domain\Identity;

final class StaffAuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $sessionToken = null,
        public readonly ?string $staffId = null,
        public readonly bool $mustChangePassword = false,
        public readonly ?string $errorCode = null,
    ) {}
}
