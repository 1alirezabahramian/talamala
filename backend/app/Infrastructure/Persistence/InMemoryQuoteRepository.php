<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence;

use Talamala\Domain\Quote\Quote;
use Talamala\Domain\Quote\QuoteRepository;
use Talamala\Domain\Quote\QuoteStatus;

final class InMemoryQuoteRepository implements QuoteRepository
{
    /** @var array<string, Quote> */
    private array $quotes = [];

    public function save(Quote $quote): void
    {
        $this->quotes[$quote->tenantId . ':' . $quote->id] = $quote;
    }

    public function findById(string $tenantId, string $quoteId): ?Quote
    {
        return $this->quotes[$tenantId . ':' . $quoteId] ?? null;
    }

    public function markAccepted(string $tenantId, string $quoteId): void
    {
        $q = $this->findById($tenantId, $quoteId);
        if ($q === null) {
            return;
        }
        $this->quotes[$tenantId . ':' . $quoteId] = new Quote(
            $q->id,
            $q->tenantId,
            $q->customerId,
            $q->side,
            $q->asset,
            $q->quantity,
            $q->unitPriceRial,
            $q->totalRial,
            $q->issuedAt,
            $q->expiresAt,
            QuoteStatus::Accepted,
            $q->priceSourceRef,
            $q->metadata,
        );
    }

    public function markExpired(string $tenantId, string $quoteId): void
    {
        $q = $this->findById($tenantId, $quoteId);
        if ($q === null) {
            return;
        }
        $this->quotes[$tenantId . ':' . $quoteId] = new Quote(
            $q->id,
            $q->tenantId,
            $q->customerId,
            $q->side,
            $q->asset,
            $q->quantity,
            $q->unitPriceRial,
            $q->totalRial,
            $q->issuedAt,
            $q->expiresAt,
            QuoteStatus::Expired,
            $q->priceSourceRef,
            $q->metadata,
        );
    }
}
