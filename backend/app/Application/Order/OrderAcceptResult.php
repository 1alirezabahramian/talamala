<?php

declare(strict_types=1);

namespace Talamala\Application\Order;

use Talamala\Domain\Order\Order;
use Talamala\Domain\Order\OrderStatus;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteSide;

final class OrderAcceptResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?Order $order = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $fromIdempotencyCache = false,
    ) {}

    public static function ok(Order $order): self
    {
        return new self(true, $order);
    }

    public static function fail(string $code, string $message): self
    {
        return new self(false, errorCode: $code, errorMessage: $message);
    }

    public static function fromCached(array $payload): self
    {
        // Minimal reconstruction for idempotent replay
        $order = new Order(
            id: (string) $payload['order_id'],
            tenantId: 'cached',
            customerId: 'cached',
            quoteId: 'cached',
            side: QuoteSide::Buy,
            asset: QuoteAsset::Gold18,
            quantity: '0',
            unitPriceRial: '0',
            totalRial: '0',
            status: OrderStatus::from((string) ($payload['status'] ?? 'accepted')),
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
        return new self(true, $order, fromIdempotencyCache: true);
    }
}
