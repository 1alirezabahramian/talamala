<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

/**
 * Port for external price feed.
 * Implementation BLOCKED until official provider contract + business coefficients (x/y/z) are grounded.
 */
interface PriceProvider
{
    /**
     * @return array{unit_price_rial: string, source_ref: string, observed_at: \DateTimeImmutable}
     * @throws PriceProviderUnavailableException
     */
    public function getUnitPriceRial(string $tenantId, QuoteAsset $asset, ?int $productId = null): array;
}
