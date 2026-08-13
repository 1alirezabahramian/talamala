<?php

declare(strict_types=1);

namespace Talamala\Integrations\Jibit;

final class JibitMatchResult
{
    public function __construct(
        public readonly bool $matched,
        public readonly ?string $providerReference = null,
        public readonly ?string $rawStatus = null,
        public readonly ?string $errorCode = null,
    ) {}
}
