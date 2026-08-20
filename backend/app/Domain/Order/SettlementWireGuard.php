<?php

declare(strict_types=1);

namespace Talamala\Domain\Order;

/**
 * Hard stop: no product code may call KimiaWrite from an Order path in Cycle 6.
 * Contract completeness can be proven offline, but product wiring remains a later evidence-backed change.
 */
final class SettlementWireGuard
{
    public function __construct(private readonly SettlementContract $contract) {}

    public static function fromDefaultArchive(string $repoRoot): self
    {
        return new self(SettlementContract::fromJsonFile(
            rtrim($repoRoot, '/') . '/docs/providers/official/SETTLEMENT_CONTRACT.json'
        ));
    }

    public function refuseOrderKimiaWire(string $reason = 'order_settlement'): never
    {
        $this->contract->assertWireAllowed();
        throw new \RuntimeException('SettlementWireGuard hard-stop remains active for Cycle 6: ' . $reason);
    }

    public function apiSettlementField(): string
    {
        return $this->contract->publicSettlementStatus();
    }
}
