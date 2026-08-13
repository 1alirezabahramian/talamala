<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

interface QuoteRepository
{
    public function save(Quote $quote): void;

    public function findById(string $tenantId, string $quoteId): ?Quote;

    /**
     * Quotes are immutable: only status transitions allowed via dedicated methods.
     */
    public function markAccepted(string $tenantId, string $quoteId): void;

    public function markExpired(string $tenantId, string $quoteId): void;
}
