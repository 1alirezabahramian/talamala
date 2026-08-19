<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/** In-memory write client for tests — same input guards as HttpKimiaWriteClient. */
final class FakeKimiaWriteClient implements KimiaWriteClient
{
    private int $seq = 77192;

    /** @var list<array<string, mixed>> */
    public array $calls = [];

    public function buyGold(int $accountId, string $goldPriceRialPerGram, string $valueGrams, string $requestId, int $goldUnit = 1): KimiaWriteResult
    {
        KimiaWriteInput::assertAccountId($accountId);
        KimiaWriteInput::assertPositiveDecimal($goldPriceRialPerGram, 'GoldPrice');
        KimiaWriteInput::assertPositiveDecimal($valueGrams, 'Value');
        KimiaWriteInput::assertRequestId($requestId);
        KimiaWriteInput::assertGoldUnit($goldUnit);

        return $this->record('buy', '/api/voucher/exchangegold', 32, $accountId, [
            'GoldPrice' => $goldPriceRialPerGram,
            'Value' => $valueGrams,
            'GoldUnit' => $goldUnit,
            'RequestId' => $requestId,
        ]);
    }

    public function sellGold(int $accountId, string $goldPriceRialPerGram, string $valueGrams, string $requestId, int $goldUnit = 1): KimiaWriteResult
    {
        KimiaWriteInput::assertAccountId($accountId);
        KimiaWriteInput::assertPositiveDecimal($goldPriceRialPerGram, 'GoldPrice');
        KimiaWriteInput::assertPositiveDecimal($valueGrams, 'Value');
        KimiaWriteInput::assertRequestId($requestId);
        KimiaWriteInput::assertGoldUnit($goldUnit);

        return $this->record('sell', '/api/voucher/exchangegold', 64, $accountId, [
            'GoldPrice' => $goldPriceRialPerGram,
            'Value' => $valueGrams,
            'GoldUnit' => $goldUnit,
            'RequestId' => $requestId,
        ]);
    }

    public function receiveCash(int $accountId, string $valueRial, string $requestId): KimiaWriteResult
    {
        KimiaWriteInput::assertAccountId($accountId);
        KimiaWriteInput::assertPositiveDecimal($valueRial, 'Value');
        KimiaWriteInput::assertRequestId($requestId);

        return $this->record('receive', '/api/voucher/tradecash', 2, $accountId, [
            'Value' => $valueRial,
            'RequestId' => $requestId,
        ]);
    }

    public function payCash(int $accountId, string $valueRial, string $requestId): KimiaWriteResult
    {
        KimiaWriteInput::assertAccountId($accountId);
        KimiaWriteInput::assertPositiveDecimal($valueRial, 'Value');
        KimiaWriteInput::assertRequestId($requestId);

        return $this->record('pay', '/api/voucher/tradecash', 4, $accountId, [
            'Value' => $valueRial,
            'RequestId' => $requestId,
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function record(string $op, string $path, int $action, int $accountId, array $extra): KimiaWriteResult
    {
        $id = ++$this->seq;
        $this->calls[] = array_merge(['operation' => $op, 'AccountId' => $accountId, 'Action' => $action], $extra);

        return new KimiaWriteResult(200, $id, null, $path, $action, $op);
    }
}
