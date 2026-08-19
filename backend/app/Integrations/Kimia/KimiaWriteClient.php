<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * Anti-Corruption Layer — Write side (bounded).
 * Only operations proven by live Batch V1 evidence (2026-08-19):
 *   POST /api/voucher/exchangegold Action 32 buy / 64 sell
 *   POST /api/voucher/tradecash   Action 2 receive / 4 pay
 *
 * Controllers must never call this directly.
 * Production product wiring still requires app-level policy; this client is the grounded HTTP contract.
 * Create Customer, Coin, Currency, Physical, Settlement are NOT in this interface.
 */
interface KimiaWriteClient
{
    /**
     * Gold buy — Action 32 on exchangegold.
     * @param string $goldPriceRialPerGram decimal string (Rial/gram when GoldUnit=1)
     * @param string $valueGrams decimal string quantity in grams when GoldUnit=1
     */
    public function buyGold(
        int $accountId,
        string $goldPriceRialPerGram,
        string $valueGrams,
        string $requestId,
        int $goldUnit = 1,
    ): KimiaWriteResult;

    /** Gold sell — Action 64 on exchangegold. */
    public function sellGold(
        int $accountId,
        string $goldPriceRialPerGram,
        string $valueGrams,
        string $requestId,
        int $goldUnit = 1,
    ): KimiaWriteResult;

    /** Cash receive — Action 2 on tradecash. Value in Rial. */
    public function receiveCash(int $accountId, string $valueRial, string $requestId): KimiaWriteResult;

    /** Cash pay — Action 4 on tradecash. Value in Rial. */
    public function payCash(int $accountId, string $valueRial, string $requestId): KimiaWriteResult;
}
