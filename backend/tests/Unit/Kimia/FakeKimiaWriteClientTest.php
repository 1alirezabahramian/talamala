<?php

declare(strict_types=1);

namespace Talamala\Tests\Unit\Kimia;

use PHPUnit\Framework\TestCase;
use Talamala\Integrations\Kimia\FakeKimiaWriteClient;
use Talamala\Integrations\Kimia\HttpKimiaWriteClient;

final class FakeKimiaWriteClientTest extends TestCase
{
    public function test_batch_v1_operations_return_live_shape_ids(): void
    {
        $c = new FakeKimiaWriteClient();
        $b = $c->buyGold(350, '181000000', '0.2', '5bf941ef-02b7-465f-abf6-fc17021baa71');
        $s = $c->sellGold(350, '181000000', '0.2', '1a4e8610-3b8e-49b9-9058-deeb1d610269');
        $r = $c->receiveCash(350, '36200000', 'cceb3541-da21-44f8-b51e-6e8ff1c70cb6');
        $p = $c->payCash(350, '36200000', '0e526d7d-4ee7-430b-8727-968396e15610');

        $this->assertSame(77193, $b->recordId);
        $this->assertSame(77194, $s->recordId);
        $this->assertSame(77195, $r->recordId);
        $this->assertSame(77196, $p->recordId);
        $this->assertSame(32, $b->action);
        $this->assertSame(64, $s->action);
        $this->assertSame(2, $r->action);
        $this->assertSame(4, $p->action);
        $this->assertNull($b->rawDecoded);
        $this->assertSame('5bf941ef-02b7-465f-abf6-fc17021baa71', $c->calls[0]['RequestId']);
        $this->assertCount(4, $c->calls);
    }

    public function test_http_payload_encoder_preserves_exact_decimal_without_float(): void
    {
        $c = new HttpKimiaWriteClient('https://example.invalid', 'u', 'p');
        $m = new \ReflectionMethod($c, 'encodePayload');
        $m->setAccessible(true);
        $body = $m->invoke($c, [
            'AccountId' => 350,
            'Action' => 32,
            'GoldPrice' => '181000000.123456789',
            'Value' => '0.200000001',
            'GoldUnit' => 1,
            'RequestId' => '5bf941ef-02b7-465f-abf6-fc17021baa71',
        ], ['GoldPrice', 'Value']);
        $this->assertStringContainsString('"GoldPrice":181000000.123456789', $body);
        $this->assertStringContainsString('"Value":0.200000001', $body);
        $this->assertStringNotContainsString('"GoldPrice":"181000000.123456789"', $body);
    }

    public function test_http_client_rejects_invalid_uuid_before_network(): void
    {
        $c = new HttpKimiaWriteClient('https://example.invalid', 'u', 'p');
        $this->expectException(\InvalidArgumentException::class);
        $c->buyGold(350, '181000000', '0.2', 'not-a-uuid');
    }
}
