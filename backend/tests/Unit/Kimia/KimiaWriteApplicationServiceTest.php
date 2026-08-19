<?php

declare(strict_types=1);

namespace Talamala\Tests\Unit\Kimia;

use PHPUnit\Framework\TestCase;
use Talamala\Application\Kimia\KimiaWriteApplicationService;
use Talamala\Integrations\Kimia\FakeKimiaWriteClient;
use Talamala\Integrations\Kimia\KimiaReadClient;
use Talamala\Integrations\Kimia\KimiaWriteClient;
use Talamala\Integrations\Kimia\KimiaWriteResult;

final class KimiaWriteApplicationServiceTest extends TestCase
{
    public function test_buy_reads_balance_before_and_after_and_generates_uuid_v4(): void
    {
        $read = new RecordingReadClient();
        $write = new FakeKimiaWriteClient();
        $svc = new KimiaWriteApplicationService($write, $read);

        $out = $svc->buyGold(350, '181000000', '0.2');

        $this->assertSame(32, $out['write']->action);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $out['request_id']);
        $this->assertSame([['snapshot' => 1]], $out['balance_before']);
        $this->assertSame([['snapshot' => 2]], $out['balance_after']);
        $this->assertSame(2, $read->balanceReads);
        $this->assertCount(1, $write->calls);
        $this->assertSame($out['request_id'], $write->calls[0]['RequestId']);
    }

    public function test_supplied_request_id_is_preserved(): void
    {
        $read = new RecordingReadClient();
        $write = new FakeKimiaWriteClient();
        $svc = new KimiaWriteApplicationService($write, $read);
        $rid = '5bf941ef-02b7-465f-abf6-fc17021baa71';

        $out = $svc->receiveCash(350, '36200000', $rid);

        $this->assertSame($rid, $out['request_id']);
        $this->assertSame($rid, $write->calls[0]['RequestId']);
        $this->assertSame(2, $read->balanceReads);
    }

    public function test_write_failure_does_not_claim_after_readback(): void
    {
        $read = new RecordingReadClient();
        $write = new ThrowingWriteClient();
        $svc = new KimiaWriteApplicationService($write, $read);

        try {
            $svc->payCash(350, '36200000', '0e526d7d-4ee7-430b-8727-968396e15610');
            self::fail('Expected write failure');
        } catch (\RuntimeException $e) {
            self::assertSame('simulated write failure', $e->getMessage());
        }

        $this->assertSame(1, $read->balanceReads, 'Only the before-read is allowed when write throws.');
    }
}

final class RecordingReadClient implements KimiaReadClient
{
    public int $balanceReads = 0;
    public function listAccounts(?int $type = null): array { return []; }
    public function listAccountGroups(int $accountType): array { return []; }
    public function getBalance(int $accountId, bool $includePeaks = false): array { ++$this->balanceReads; return [['snapshot' => $this->balanceReads]]; }
    public function getTransactions(int $accountId, array $query = []): array { return []; }
    public function listCoins(): array { return []; }
    public function listCurrencies(): array { return []; }
}

final class ThrowingWriteClient implements KimiaWriteClient
{
    public function buyGold(int $accountId, string $goldPriceRialPerGram, string $valueGrams, string $requestId, int $goldUnit = 1): KimiaWriteResult { throw new \RuntimeException('simulated write failure'); }
    public function sellGold(int $accountId, string $goldPriceRialPerGram, string $valueGrams, string $requestId, int $goldUnit = 1): KimiaWriteResult { throw new \RuntimeException('simulated write failure'); }
    public function receiveCash(int $accountId, string $valueRial, string $requestId): KimiaWriteResult { throw new \RuntimeException('simulated write failure'); }
    public function payCash(int $accountId, string $valueRial, string $requestId): KimiaWriteResult { throw new \RuntimeException('simulated write failure'); }
}
