<?php

declare(strict_types=1);

namespace Talamala\Application\Kimia;

use Talamala\Integrations\Kimia\KimiaReadClient;
use Talamala\Integrations\Kimia\KimiaWriteClient;
use Talamala\Integrations\Kimia\KimiaWriteResult;

/**
 * Application use-case for bounded Kimia writes (Batch V1 ops only).
 * Domain rule: after successful Kimia write → mandatory readback.
 *
 * Does NOT settle orders, create customers, or invent pricing.
 * Controllers/Order must not call Integrations directly; they may use this service
 * only when product GT for that flow is closed.
 */
final class KimiaWriteApplicationService
{
    public function __construct(
        private readonly KimiaWriteClient $write,
        private readonly KimiaReadClient $read,
    ) {}

    /**
     * @return array{
     *   write: KimiaWriteResult,
     *   balance_before: list<array<string, mixed>>,
     *   balance_after: list<array<string, mixed>>,
     *   request_id: string
     * }
     */
    public function buyGold(
        int $kimiaAccountId,
        string $goldPriceRialPerGram,
        string $valueGrams,
        ?string $requestId = null,
        int $goldUnit = 1,
    ): array {
        return $this->runWithReadback(
            $kimiaAccountId,
            $requestId,
            fn (string $rid): KimiaWriteResult => $this->write->buyGold(
                $kimiaAccountId,
                $goldPriceRialPerGram,
                $valueGrams,
                $rid,
                $goldUnit,
            ),
        );
    }

    /** @return array{write: KimiaWriteResult, balance_before: list<array<string,mixed>>, balance_after: list<array<string,mixed>>, request_id: string} */
    public function sellGold(int $kimiaAccountId, string $goldPriceRialPerGram, string $valueGrams, ?string $requestId = null, int $goldUnit = 1): array
    {
        return $this->runWithReadback(
            $kimiaAccountId,
            $requestId,
            fn (string $rid): KimiaWriteResult => $this->write->sellGold($kimiaAccountId, $goldPriceRialPerGram, $valueGrams, $rid, $goldUnit),
        );
    }

    /** @return array{write: KimiaWriteResult, balance_before: list<array<string,mixed>>, balance_after: list<array<string,mixed>>, request_id: string} */
    public function receiveCash(int $kimiaAccountId, string $valueRial, ?string $requestId = null): array
    {
        return $this->runWithReadback(
            $kimiaAccountId,
            $requestId,
            fn (string $rid): KimiaWriteResult => $this->write->receiveCash($kimiaAccountId, $valueRial, $rid),
        );
    }

    /** @return array{write: KimiaWriteResult, balance_before: list<array<string,mixed>>, balance_after: list<array<string,mixed>>, request_id: string} */
    public function payCash(int $kimiaAccountId, string $valueRial, ?string $requestId = null): array
    {
        return $this->runWithReadback(
            $kimiaAccountId,
            $requestId,
            fn (string $rid): KimiaWriteResult => $this->write->payCash($kimiaAccountId, $valueRial, $rid),
        );
    }

    /**
     * @param callable(string): KimiaWriteResult $mutator
     * @return array{write: KimiaWriteResult, balance_before: list<array<string,mixed>>, balance_after: list<array<string,mixed>>, request_id: string}
     */
    private function runWithReadback(int $accountId, ?string $requestId, callable $mutator): array
    {
        $rid = $requestId ?? $this->newUuidV4();
        $before = $this->read->getBalance($accountId, false);
        $write = $mutator($rid);
        $after = $this->read->getBalance($accountId, false);

        return [
            'write' => $write,
            'balance_before' => $before,
            'balance_after' => $after,
            'request_id' => $rid,
        ];
    }

    private function newUuidV4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $h = bin2hex($b);
        return substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4)
            . '-' . substr($h, 16, 4) . '-' . substr($h, 20, 12);
    }
}
