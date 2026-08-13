<?php

declare(strict_types=1);

namespace Talamala\Domain\Identity;

final class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $sessionToken = null,
        public readonly ?string $customerId = null,
        public readonly ?CustomerAccessStatus $accessStatus = null,
        public readonly bool $requiresRegistration = false,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function success(string $sessionToken, string $customerId, CustomerAccessStatus $status): self
    {
        return new self(true, $sessionToken, $customerId, $status);
    }

    public static function needsRegistration(): self
    {
        return new self(true, requiresRegistration: true);
    }

    public static function failure(string $code, string $message): self
    {
        return new self(false, errorCode: $code, errorMessage: $message);
    }
}
