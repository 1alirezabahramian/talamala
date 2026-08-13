<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

class KimiaException extends \RuntimeException {}

final class KimiaTransportException extends KimiaException {}

final class KimiaAuthException extends KimiaException {}

final class KimiaHttpException extends KimiaException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly ?string $responseBody = null,
    ) {
        parent::__construct($message, $httpStatus);
    }
}

final class KimiaUnexpectedResponseException extends KimiaException {}
