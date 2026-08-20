<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

/**
 * Default PriceProvider while GT-004 provider evidence is incomplete.
 *
 * This adapter intentionally never returns a synthetic market price. Even if
 * the business-policy slice is later ratified, a real provider implementation
 * must be introduced in a separate evidenced change.
 */
final class BlockedPriceProvider implements PriceProvider
{
    public function __construct(private readonly PricingContract $contract) {}

    public static function fromDefaultArchive(string $repoRoot): self
    {
        $path = rtrim($repoRoot, '/') . '/docs/providers/official/PRICING_CONTRACT.json';
        return new self(PricingContract::fromJsonFile($path));
    }

    public function getUnitPriceRial(string $tenantId, QuoteAsset $asset, ?int $productId = null): array
    {
        $this->contract->assertLivePricingAllowed();

        // Deliberate hard stop: this class is not a live HTTP provider.
        throw PriceProviderUnavailableException::blockedByGroundTruth();
    }
}
