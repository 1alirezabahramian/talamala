<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * Result placeholder — id field name comes from grounded contract when available.
 */
final class KimiaCreateCustomerResult
{
    /** @param array<string, mixed>|null $rawDecoded */
    public function __construct(
        public readonly int $httpStatus,
        public readonly int|string|null $accountId,
        public readonly ?array $rawDecoded,
        public readonly string $path,
    ) {}
}
