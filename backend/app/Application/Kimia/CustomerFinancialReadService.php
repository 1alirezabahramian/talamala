<?php

declare(strict_types=1);

namespace Talamala\Application\Kimia;

use Talamala\Integrations\Kimia\KimiaReadClient;

/**
 * Stage 3 — maps Kimia balance payload to customer presentation (Toman).
 * Rial → Toman only here (÷ 10). No float: use string math for money.
 * CurrencyId determines meaning of Money field (per domain evidence).
 */
final class CustomerFinancialReadService
{
    /** Known from runtime evidence: CurrencyId 11 = Rial */
    private const CURRENCY_RIAL = 11;

    public function __construct(
        private readonly KimiaReadClient $kimia,
    ) {}

    /**
     * @return array{
     *   money_toman: string,
     *   gold_weight_g: string,
     *   lines: list<array{currency_id:int,currency_symbol:?string,money:string,weight:string}>
     * }
     */
    public function assetsForKimiaAccount(int $kimiaAccountId): array
    {
        $balances = $this->kimia->getBalance($kimiaAccountId, false);

        $moneyToman = '0';
        $goldWeight = '0';
        $lines = [];

        foreach ($balances as $row) {
            if (!is_array($row)) {
                continue;
            }
            $currencyId = (int) ($row['CurrencyId'] ?? 0);
            $money = (string) ($row['Money'] ?? '0');
            $weight = (string) ($row['Weight'] ?? '0');
            $symbol = isset($row['CurrencySymbol']) ? (string) $row['CurrencySymbol'] : null;

            $lines[] = [
                'currency_id' => $currencyId,
                'currency_symbol' => $symbol,
                'money' => $money,
                'weight' => $weight,
            ];

            if ($currencyId === self::CURRENCY_RIAL) {
                $moneyToman = $this->rialToToman($money);
            }
            // Gold weight often appears on the Rial line as Weight; also accept any non-zero weight
            if ($this->bcComp($weight, '0') !== 0) {
                $goldWeight = $weight;
            }
        }

        return [
            'money_toman' => $moneyToman,
            'gold_weight_g' => $goldWeight,
            'lines' => $lines,
        ];
    }

    private function rialToToman(string $rial): string
    {
        // Exact integer division by 10 when possible; keep remainder awareness
        if (function_exists('bcdiv')) {
            return bcdiv($rial, '10', 0);
        }
        // Fallback without float for whole rials
        if (str_contains($rial, '.')) {
            throw new \RuntimeException('Fractional rial requires bcmath');
        }
        $neg = str_starts_with($rial, '-');
        $digits = ltrim($rial, '-');
        if ($digits === '') {
            return '0';
        }
        if (strlen($digits) === 1) {
            return '0';
        }
        $toman = substr($digits, 0, -1);
        return ($neg ? '-' : '') . ($toman === '' ? '0' : $toman);
    }

    private function bcComp(string $a, string $b): int
    {
        if (function_exists('bccomp')) {
            return bccomp($a, $b, 8);
        }
        return $a <=> $b;
    }
}
