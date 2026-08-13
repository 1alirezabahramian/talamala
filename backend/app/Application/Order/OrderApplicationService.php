<?php

declare(strict_types=1);

namespace Talamala\Application\Order;

use Talamala\Domain\Audit\AuditEvent;
use Talamala\Domain\Audit\AuditLogger;
use Talamala\Domain\Idempotency\IdempotencyKey;
use Talamala\Domain\Idempotency\IdempotencyRegistry;
use Talamala\Domain\Order\Order;
use Talamala\Domain\Order\OrderRepository;
use Talamala\Domain\Order\OrderStatus;
use Talamala\Domain\Quote\Quote;
use Talamala\Domain\Quote\QuoteRepository;
use Talamala\Domain\Quote\QuoteStatus;

/**
 * Accept order from immutable quote.
 * Does NOT call Kimia write — settlement path explicitly blocked.
 */
final class OrderApplicationService
{
    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly OrderRepository $orders,
        private readonly IdempotencyRegistry $idempotency,
        private readonly AuditLogger $audit,
    ) {}

    public function acceptFromQuote(
        string $tenantId,
        string $customerId,
        string $quoteId,
        string $idempotencyKey,
        string $correlationId,
    ): OrderAcceptResult {
        $idem = new IdempotencyKey($tenantId, $idempotencyKey, 'order.accept');
        $cached = $this->idempotency->find($idem);
        if ($cached !== null) {
            return OrderAcceptResult::fromCached($cached);
        }

        $quote = $this->quotes->findById($tenantId, $quoteId);
        if ($quote === null) {
            return OrderAcceptResult::fail('quote_not_found', 'Quote not found');
        }
        if ($quote->customerId !== $customerId) {
            return OrderAcceptResult::fail('quote_owner_mismatch', 'Quote does not belong to customer');
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if (!$quote->isAcceptable($now)) {
            return OrderAcceptResult::fail('quote_not_acceptable', 'Quote expired or not open');
        }

        $order = new Order(
            id: bin2hex(random_bytes(12)),
            tenantId: $tenantId,
            customerId: $customerId,
            quoteId: $quote->id,
            side: $quote->side,
            asset: $quote->asset,
            quantity: $quote->quantity,
            unitPriceRial: $quote->unitPriceRial,
            totalRial: $quote->totalRial,
            status: OrderStatus::Accepted,
            createdAt: $now,
            idempotencyKey: $idempotencyKey,
        );
        $this->orders->save($order);
        $this->quotes->markAccepted($tenantId, $quoteId);

        $this->audit->log(new AuditEvent(
            id: bin2hex(random_bytes(8)),
            tenantId: $tenantId,
            actorId: $customerId,
            actorType: 'customer',
            action: 'order.accepted',
            targetType: 'order',
            targetId: $order->id,
            reason: null,
            correlationId: $correlationId,
            metadata: ['quote_id' => $quoteId],
            occurredAt: $now,
        ));

        $payload = [
            'order_id' => $order->id,
            'status' => $order->status->value,
            'settlement' => 'blocked_by_ground_truth',
        ];
        $this->idempotency->store($idem, $payload, $now->modify('+24 hours'));

        return OrderAcceptResult::ok($order);
    }

    /**
     * Settlement intentionally refuses until Kimia write is grounded.
     */
    public function attemptSettlement(string $tenantId, string $orderId): OrderAcceptResult
    {
        return OrderAcceptResult::fail(
            'settlement_blocked',
            'Kimia write path BLOCKED BY GROUND TRUTH — no settlement execution',
        );
    }
}
