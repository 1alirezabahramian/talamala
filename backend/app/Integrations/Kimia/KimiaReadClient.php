<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * Anti-Corruption Layer — Read side only.
 * Controllers must never call Kimia HTTP directly.
 * Write client is separate and remains BLOCKED until exact contracts are grounded.
 */
interface KimiaReadClient
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listAccounts(?int $type = null): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listAccountGroups(int $accountType): array;

    /**
     * Balance for a Kimia account id. Money semantics depend on CurrencyId.
     * @return list<array<string, mixed>>
     */
    public function getBalance(int $accountId, bool $includePeaks = false): array;

    /**
     * @return array<string, mixed>
     */
    public function getTransactions(int $accountId, array $query = []): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listCoins(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function listCurrencies(): array;
}
