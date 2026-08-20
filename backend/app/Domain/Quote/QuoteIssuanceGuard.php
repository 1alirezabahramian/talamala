<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

/**
 * Quote issuance boundary for GT-004.
 *
 * Non-live fixture/manual/dev snapshots may be issued while GT-004 is
 * incomplete. Any other source must pass the full PricingContract live gate.
 */
final class QuoteIssuanceGuard
{
    public function __construct(private readonly PricingContract $contract) {}

    public static function fromDefaultArchive(string $repoRoot): self
    {
        $path = rtrim($repoRoot, '/') . '/docs/providers/official/PRICING_CONTRACT.json';
        return new self(PricingContract::fromJsonFile($path));
    }

    public function assertLiveIssueAllowed(): void
    {
        $this->contract->assertLivePricingAllowed();
    }

    public function assertSourceAllowed(?string $priceSourceRef): void
    {
        $ref = trim((string) $priceSourceRef);
        if ($ref === '') {
            throw new PriceProviderUnavailableException('quote price_source_ref is required');
        }

        if ($this->isExplicitNonLiveSource($ref)) {
            return;
        }

        // Important: do not merely check GROUNDED+authorized flags here.
        // Reuse the complete provider/freshness/rounding/TTL/unknowns gate.
        $this->contract->assertLivePricingAllowed();
    }

    public function isLivePricingOpen(): bool
    {
        try {
            $this->contract->assertLivePricingAllowed();
            return true;
        } catch (PriceProviderUnavailableException) {
            return false;
        }
    }

    private function isExplicitNonLiveSource(string $ref): bool
    {
        return str_starts_with($ref, 'dev-')
            || str_starts_with($ref, 'fixture-')
            || str_starts_with($ref, 'manual-');
    }
}
