<?php

declare(strict_types=1);

namespace Talamala\Tests\Feature\Order;

use PHPUnit\Framework\TestCase;
use Talamala\Application\Order\OrderApplicationService;
use Talamala\Domain\Order\OrderStatus;
use Talamala\Domain\Quote\Quote;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteSide;
use Talamala\Domain\Quote\QuoteStatus;
use Talamala\Infrastructure\Persistence\InMemoryAuditLogger;
use Talamala\Infrastructure\Persistence\InMemoryIdempotencyRegistry;
use Talamala\Infrastructure\Persistence\InMemoryOrderRepository;
use Talamala\Infrastructure\Persistence\InMemoryQuoteRepository;

final class OrderAcceptTest extends TestCase
{
    public function testAcceptQuoteCreatesOrderAndBlocksSettlement(): void
    {
        $quotes = new InMemoryQuoteRepository();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $quote = new Quote(
            id: 'q1',
            tenantId: 't1',
            customerId: 'c1',
            side: QuoteSide::Buy,
            asset: QuoteAsset::Gold18,
            quantity: '1.000',
            unitPriceRial: '100000000',
            totalRial: '100000000',
            issuedAt: $now,
            expiresAt: $now->modify('+5 minutes'),
            status: QuoteStatus::Open,
            priceSourceRef: 'test-manual',
        );
        $quotes->save($quote);

        $svc = new OrderApplicationService(
            $quotes,
            new InMemoryOrderRepository(),
            new InMemoryIdempotencyRegistry(),
            new InMemoryAuditLogger(),
        );

        $result = $svc->acceptFromQuote('t1', 'c1', 'q1', 'idem-1', 'corr-1');
        $this->assertTrue($result->success);
        $this->assertSame(OrderStatus::Accepted, $result->order->status);

        // Idempotent replay
        $result2 = $svc->acceptFromQuote('t1', 'c1', 'q1', 'idem-1', 'corr-2');
        $this->assertTrue($result2->fromIdempotencyCache);

        $settle = $svc->attemptSettlement('t1', $result->order->id);
        $this->assertFalse($settle->success);
        $this->assertSame('settlement_blocked', $settle->errorCode);
    }
}
