<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;
use Talamala\Domain\Order\Order;
use Talamala\Domain\Order\OrderRepository;
use Talamala\Domain\Order\OrderStatus;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteSide;

final class SqliteOrderRepository implements OrderRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function save(Order $order): void
    {
        $st = $this->pdo->prepare(<<<'SQL'
INSERT INTO orders (
    id, tenant_id, customer_id, quote_id, side, asset, quantity,
    unit_price_rial, total_rial, status, idempotency_key, kimia_record_id,
    failure_reason, created_at
) VALUES (
    :id, :tenant_id, :customer_id, :quote_id, :side, :asset, :quantity,
    :unit_price_rial, :total_rial, :status, :idempotency_key, :kimia_record_id,
    :failure_reason, :created_at
)
ON CONFLICT(id) DO UPDATE SET
    status = excluded.status,
    kimia_record_id = excluded.kimia_record_id,
    failure_reason = excluded.failure_reason
SQL);
        $st->execute([
            'id' => $order->id,
            'tenant_id' => $order->tenantId,
            'customer_id' => $order->customerId,
            'quote_id' => $order->quoteId,
            'side' => $order->side->value,
            'asset' => $order->asset->value,
            'quantity' => $order->quantity,
            'unit_price_rial' => $order->unitPriceRial,
            'total_rial' => $order->totalRial,
            'status' => $order->status->value,
            'idempotency_key' => $order->idempotencyKey,
            'kimia_record_id' => $order->kimiaRecordId,
            'failure_reason' => $order->failureReason,
            'created_at' => $order->createdAt->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function findById(string $tenantId, string $orderId): ?Order
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM orders WHERE tenant_id = :t AND id = :id LIMIT 1'
        );
        $st->execute(['t' => $tenantId, 'id' => $orderId]);
        $row = $st->fetch();
        return $row ? $this->map($row) : null;
    }

    public function listForCustomer(string $tenantId, string $customerId, int $limit = 50): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM orders WHERE tenant_id = :t AND customer_id = :c
             ORDER BY created_at DESC LIMIT :lim'
        );
        $st->bindValue('t', $tenantId);
        $st->bindValue('c', $customerId);
        $st->bindValue('lim', $limit, PDO::PARAM_INT);
        $st->execute();
        $out = [];
        while ($row = $st->fetch()) {
            $out[] = $this->map($row);
        }
        return $out;
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Order
    {
        return new Order(
            id: (string) $row['id'],
            tenantId: (string) $row['tenant_id'],
            customerId: (string) $row['customer_id'],
            quoteId: (string) $row['quote_id'],
            side: QuoteSide::from((string) $row['side']),
            asset: QuoteAsset::from((string) $row['asset']),
            quantity: (string) $row['quantity'],
            unitPriceRial: (string) $row['unit_price_rial'],
            totalRial: (string) $row['total_rial'],
            status: OrderStatus::from((string) $row['status']),
            createdAt: new \DateTimeImmutable((string) $row['created_at']),
            idempotencyKey: $row['idempotency_key'] !== null ? (string) $row['idempotency_key'] : null,
            kimiaRecordId: $row['kimia_record_id'] !== null ? (string) $row['kimia_record_id'] : null,
            failureReason: $row['failure_reason'] !== null ? (string) $row['failure_reason'] : null,
        );
    }
}
