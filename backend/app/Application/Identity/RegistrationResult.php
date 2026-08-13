<?php

declare(strict_types=1);

namespace Talamala\Application\Identity;

use Talamala\Domain\Identity\Customer;

final class RegistrationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?Customer $customer = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function ok(Customer $customer): self
    {
        return new self(true, $customer);
    }

    public static function fail(string $code, string $message): self
    {
        return new self(false, errorCode: $code, errorMessage: $message);
    }
}
