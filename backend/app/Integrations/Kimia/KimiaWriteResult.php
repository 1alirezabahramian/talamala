<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/** Outcome of a grounded Kimia write. */
final class KimiaWriteResult
{
    /** @param array<string, mixed>|null $rawDecoded */
    public function __construct(
        public readonly int $httpStatus,
        public readonly int|string|null $recordId,
        public readonly ?array $rawDecoded,
        public readonly string $endpoint,
        public readonly int $action,
        public readonly string $operation,
    ) {}
}
