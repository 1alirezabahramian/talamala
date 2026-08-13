<?php

declare(strict_types=1);

namespace Talamala\Integrations\Sms;

final class SmsSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $messageId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}
}
