<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;
use Talamala\Domain\Quote\Quote;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteRepository;
use Talamala\Domain\Quote\QuoteSide;
use Talamala\Domain\Quote\QuoteStatus;

final class SqliteQuoteRepository implements QuoteRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function save(Quote $quote): void
    {
        $st = $this->pdo->prepare(<<<'SQL'
INSERT INTO quotes (
    id, tenant_id, customer_id, side, asset, quantity, unit_price_rial, total_rial,
    issued_at, expires_at, status, price_source_ref, metadata_json
) VALUES (
    :id, :tenant_id, :customer_id, :side, :asset, :quantity, :unit_price_rial, :total_rial,
    :issued_at, :expires_at, :status, :price_source_ref, :metadata_json
)
ON CONFLICT(id) DO UPDATE SET
    status = excluded.status,
    metadata_json = excluded.metadata_json
SQL);
        $st->execute([
            'id' => $quote->id,
            'tenant_id' => $quote->tenantId,
            'customer_id' => $quote->customerId,
            'side' => $quote->side->value,
            'asset' => $quote->asset->value,
            'quantity' => $quote->quantity,
            'unit_price_rial' => $quote->unitPriceRial,
            'total_rial' => $quote->totalRial,
            'issued_at' => $quote->issuedAt->format(\DateTimeInterface::ATOM),
            'expires_at' => $quote->expiresAt->format(\DateTimeInterface::ATOM),
            'status' => $quote->status->value,
            'price_source_ref' => $quote->priceSourceRef,
            'metadata_json' => json_encode($quote->metadata, JSON_UNESCAPED_UNICODE) ?: '{}',
        ]);
    }

    public function findById(string $tenantId, string $quoteId): ?Quote
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM quotes WHERE tenant_id = :t AND id = :id LIMIT 1'
        );
        $st->execute(['t' => $tenantId, 'id' => $quoteId]);
        $row = $st->fetch();
        return $row ? $this->map($row) : null;
    }

    public function markAccepted(string $tenantId, string $quoteId): void
    {
        $this->setStatus($tenantId, $quoteId, QuoteStatus::Accepted);
    }

    public function markExpired(string $tenantId, string $quoteId): void
    {
        $this->setStatus($tenantId, $quoteId, QuoteStatus::Expired);
    }

    private function setStatus(string $tenantId, string $quoteId, QuoteStatus $status): void
    {
        $st = $this->pdo->prepare(
            'UPDATE quotes SET status = :s WHERE tenant_id = :t AND id = :id'
        );
        $st->execute(['s' => $status->value, 't' => $tenantId, 'id' => $quoteId]);
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Quote
    {
        $meta = [];
        if (!empty($row['metadata_json'])) {
            $decoded = json_decode((string) $row['metadata_json'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }
        return new Quote(
            id: (string) $row['id'],
            tenantId: (string) $row['tenant_id'],
            customerId: (string) $row['customer_id'],
            side: QuoteSide::from((string) $row['side']),
            asset: QuoteAsset::from((string) $row['asset']),
            quantity: (string) $row['quantity'],
            unitPriceRial: (string) $row['unit_price_rial'],
            totalRial: (string) $row['total_rial'],
            issuedAt: new \DateTimeImmutable((string) $row['issued_at']),
            expiresAt: new \DateTimeImmutable((string) $row['expires_at']),
            status: QuoteStatus::from((string) $row['status']),
            priceSourceRef: $row['price_source_ref'] !== null ? (string) $row['price_source_ref'] : null,
            metadata: $meta,
        );
    }
}
