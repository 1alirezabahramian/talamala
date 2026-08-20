<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

use Talamala\Domain\Shared\DecimalString;

/**
 * Immutable quote snapshot.
 * Once issued, fields must not change. Order references quote_id only.
 * Price coefficients (x/y/z) are NOT invented here — remain BLOCKED until provider ground truth.
 */
final class Quote
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $customerId,
        public readonly QuoteSide $side,
        public readonly QuoteAsset $asset,
        public readonly string $quantity,       // decimal string
        public readonly string $unitPriceRial,  // rial, decimal string
        public readonly string $totalRial,      // rial, decimal string
        public readonly \DateTimeImmutable $issuedAt,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly QuoteStatus $status,
        public readonly ?string $priceSourceRef = null,
        public readonly array $metadata = [],
    ) {
        DecimalString::assertCanonical($quantity, 'quantity');
        DecimalString::assertCanonical($unitPriceRial, 'unitPriceRial');
        DecimalString::assertCanonical($totalRial, 'totalRial');
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }

    public function isAcceptable(\DateTimeImmutable $now): bool
    {
        return $this->status === QuoteStatus::Open && !$this->isExpired($now);
    }
}
