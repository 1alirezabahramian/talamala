<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * Test double matching observed BalanceDto shape from domain workshop / swagger.
 */
final class FakeKimiaReadClient implements KimiaReadClient
{
    /** @var array<int, list<array<string, mixed>>> */
    private array $balances = [];

    public function seedBalance(int $accountId, array $rows): void
    {
        $this->balances[$accountId] = $rows;
    }

    public function listAccounts(?int $type = null): array
    {
        return [
            ['AccountId' => 350, 'Name' => 'Test Customer', 'Type' => 3, 'Mobile' => '09121234567'],
        ];
    }

    public function listAccountGroups(int $accountType): array
    {
        return [['Id' => 1, 'Name' => 'تکفروشی', 'AccountType' => $accountType]];
    }

    public function getBalance(int $accountId, bool $includePeaks = false): array
    {
        return $this->balances[$accountId] ?? [];
    }

    public function getTransactions(int $accountId, array $query = []): array
    {
        return [
            'PageNumber' => 0,
            'PageSize' => 20,
            'TotalCount' => 0,
            'Items' => [],
        ];
    }

    public function listCoins(): array
    {
        return [
            ['CoinId' => 10006, 'Name' => 'سکه تمام امامی', 'Fineness' => 900, 'Weight' => 8.133, 'Type' => 15, 'IsVisible' => true],
        ];
    }

    public function listCurrencies(): array
    {
        return [
            ['CurrencyId' => 11, 'Name' => 'ریال', 'IsVisible' => true],
            ['CurrencyId' => 12, 'Name' => 'دلار', 'IsVisible' => true],
        ];
    }
}
