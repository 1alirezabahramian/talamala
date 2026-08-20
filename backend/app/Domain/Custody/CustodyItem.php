<?php

declare(strict_types=1);

namespace Talamala\Domain\Custody;

use Talamala\Domain\Shared\DecimalString;

/**
 * Physical custody (Amanat) — Talamala source of truth.
 * Independent from Kimia financial balances (Money/Gold/Coin/Currency).
 * AccountType 10 in Kimia is related but custody lifecycle is owned here.
 */
final class CustodyItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $customerId,
        public readonly string $description,
        public readonly string $weightGrams,      // decimal string
        public readonly ?string $fineness,        // e.g. "750"
        public readonly CustodyStatus $status,
        public readonly \DateTimeImmutable $receivedAt,
        public readonly ?\DateTimeImmutable $readyAt = null,
        public readonly ?\DateTimeImmutable $deliveredAt = null,
        public readonly ?string $barcodeRef = null,
        public readonly ?string $notes = null,
    ) {
        DecimalString::assertCanonical($weightGrams, 'weightGrams');
    }

    public function markReady(\DateTimeImmutable $at): self
    {
        if ($this->status !== CustodyStatus::Held) {
            throw new \DomainException('Only held items can become ready');
        }
        return new self(
            $this->id,
            $this->tenantId,
            $this->customerId,
            $this->description,
            $this->weightGrams,
            $this->fineness,
            CustodyStatus::ReadyForPickup,
            $this->receivedAt,
            $at,
            null,
            $this->barcodeRef,
            $this->notes,
        );
    }

    public function markDelivered(\DateTimeImmutable $at): self
    {
        if ($this->status !== CustodyStatus::ReadyForPickup) {
            throw new \DomainException('Only ready items can be delivered');
        }
        return new self(
            $this->id,
            $this->tenantId,
            $this->customerId,
            $this->description,
            $this->weightGrams,
            $this->fineness,
            CustodyStatus::Delivered,
            $this->receivedAt,
            $this->readyAt,
            $at,
            $this->barcodeRef,
            $this->notes,
        );
    }
}
