<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;
use Talamala\Domain\Custody\CustodyItem;
use Talamala\Domain\Custody\CustodyRepository;
use Talamala\Domain\Custody\CustodyStatus;

final class SqliteCustodyRepository implements CustodyRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function save(CustodyItem $item): void
    {
        $st = $this->pdo->prepare(<<<'SQL'
INSERT INTO custody_items (
    id, tenant_id, customer_id, description, weight_grams, fineness,
    status, received_at, ready_at, delivered_at, barcode_ref, notes
) VALUES (
    :id, :tenant_id, :customer_id, :description, :weight_grams, :fineness,
    :status, :received_at, :ready_at, :delivered_at, :barcode_ref, :notes
)
ON CONFLICT(id) DO UPDATE SET
    description = excluded.description,
    weight_grams = excluded.weight_grams,
    fineness = excluded.fineness,
    status = excluded.status,
    ready_at = excluded.ready_at,
    delivered_at = excluded.delivered_at,
    barcode_ref = excluded.barcode_ref,
    notes = excluded.notes
SQL);
        $st->execute([
            'id' => $item->id,
            'tenant_id' => $item->tenantId,
            'customer_id' => $item->customerId,
            'description' => $item->description,
            'weight_grams' => $item->weightGrams,
            'fineness' => $item->fineness,
            'status' => $item->status->value,
            'received_at' => $item->receivedAt->format(\DateTimeInterface::ATOM),
            'ready_at' => $item->readyAt?->format(\DateTimeInterface::ATOM),
            'delivered_at' => $item->deliveredAt?->format(\DateTimeInterface::ATOM),
            'barcode_ref' => $item->barcodeRef,
            'notes' => $item->notes,
        ]);
    }

    public function findById(string $tenantId, string $id): ?CustodyItem
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM custody_items WHERE tenant_id = :t AND id = :id LIMIT 1'
        );
        $st->execute(['t' => $tenantId, 'id' => $id]);
        $row = $st->fetch();
        return $row ? $this->map($row) : null;
    }

    public function listForCustomer(string $tenantId, string $customerId): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM custody_items WHERE tenant_id = :t AND customer_id = :c
             ORDER BY received_at DESC'
        );
        $st->execute(['t' => $tenantId, 'c' => $customerId]);
        $out = [];
        while ($row = $st->fetch()) {
            $out[] = $this->map($row);
        }
        return $out;
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): CustodyItem
    {
        return new CustodyItem(
            id: (string) $row['id'],
            tenantId: (string) $row['tenant_id'],
            customerId: (string) $row['customer_id'],
            description: (string) $row['description'],
            weightGrams: (string) $row['weight_grams'],
            fineness: $row['fineness'] !== null ? (string) $row['fineness'] : null,
            status: CustodyStatus::from((string) $row['status']),
            receivedAt: new \DateTimeImmutable((string) $row['received_at']),
            readyAt: $row['ready_at'] !== null ? new \DateTimeImmutable((string) $row['ready_at']) : null,
            deliveredAt: $row['delivered_at'] !== null
                ? new \DateTimeImmutable((string) $row['delivered_at'])
                : null,
            barcodeRef: $row['barcode_ref'] !== null ? (string) $row['barcode_ref'] : null,
            notes: $row['notes'] !== null ? (string) $row['notes'] : null,
        );
    }
}
