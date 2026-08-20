<?php

declare(strict_types=1);

namespace Talamala\Domain\Order;

use Talamala\Domain\Quote\QuoteSide;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Shared\DecimalString;

/**
 * Order references an immutable quote_id.
 * Financial settlement via Kimia write remains BLOCKED until write contracts grounded.
 * Local status machine only tracks platform lifecycle; balances stay in Kimia.
 */
final class Order
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $customerId,
        public readonly string $quoteId,
        public readonly QuoteSide $side,
        public readonly QuoteAsset $asset,
        public readonly string $quantity,
        public readonly string $unitPriceRial,
        public readonly string $totalRial,
        public readonly OrderStatus $status,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $kimiaRecordId = null,
        public readonly ?string $failureReason = null,
    ) {
        DecimalString::assertCanonical($quantity, 'quantity');
        DecimalString::assertCanonical($unitPriceRial, 'unitPriceRial');
        DecimalString::assertCanonical($totalRial, 'totalRial');
    }

    public function markPendingSettlement(): self
    {
        if ($this->status !== OrderStatus::Accepted) {
            throw new \DomainException('Only accepted orders can enter settlement');
        }
        return $this->withStatus(OrderStatus::PendingSettlement);
    }

    public function markSettled(string $kimiaRecordId): self
    {
        if ($this->status !== OrderStatus::PendingSettlement) {
            throw new \DomainException('Only pending settlement can settle');
        }
        return new self(
            $this->id,
            $this->tenantId,
            $this->customerId,
            $this->quoteId,
            $this->side,
            $this->asset,
            $this->quantity,
            $this->unitPriceRial,
            $this->totalRial,
            OrderStatus::Settled,
            $this->createdAt,
            $this->idempotencyKey,
            $kimiaRecordId,
            null,
        );
    }

    public function markFailed(string $reason): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->customerId,
            $this->quoteId,
            $this->side,
            $this->asset,
            $this->quantity,
            $this->unitPriceRial,
            $this->totalRial,
            OrderStatus::Failed,
            $this->createdAt,
            $this->idempotencyKey,
            $this->kimiaRecordId,
            $reason,
        );
    }

    private function withStatus(OrderStatus $status): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->customerId,
            $this->quoteId,
            $this->side,
            $this->asset,
            $this->quantity,
            $this->unitPriceRial,
            $this->totalRial,
            $status,
            $this->createdAt,
            $this->idempotencyKey,
            $this->kimiaRecordId,
            $this->failureReason,
        );
    }
}
