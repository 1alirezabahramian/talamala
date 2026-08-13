<?php

declare(strict_types=1);

namespace Talamala\Domain\Identity;

/**
 * Platform customer. Kimia account binding is optional until Stage 3+ wiring.
 * Financial balances are never stored here — only kimia_account_id reference.
 */
final class Customer
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $mobile,
        public readonly ?string $nationalCode,
        public readonly ?string $fullName,
        public readonly CustomerAccessStatus $accessStatus,
        public readonly ?int $kimiaAccountId,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $approvedAt = null,
    ) {}

    public function isBoundToKimia(): bool
    {
        return $this->kimiaAccountId !== null;
    }

    public function withKimiaBinding(int $kimiaAccountId): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->mobile,
            $this->nationalCode,
            $this->fullName,
            $this->accessStatus,
            $kimiaAccountId,
            $this->createdAt,
            $this->approvedAt,
        );
    }

    public function withAccessStatus(CustomerAccessStatus $status): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->mobile,
            $this->nationalCode,
            $this->fullName,
            $status,
            $this->kimiaAccountId,
            $this->createdAt,
            $this->approvedAt,
        );
    }
}
