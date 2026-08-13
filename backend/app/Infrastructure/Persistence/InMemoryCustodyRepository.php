<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence;

use Talamala\Domain\Custody\CustodyItem;
use Talamala\Domain\Custody\CustodyRepository;

final class InMemoryCustodyRepository implements CustodyRepository
{
    /** @var array<string, CustodyItem> */
    private array $items = [];

    public function save(CustodyItem $item): void
    {
        $this->items[$item->tenantId . ':' . $item->id] = $item;
    }

    public function findById(string $tenantId, string $id): ?CustodyItem
    {
        return $this->items[$tenantId . ':' . $id] ?? null;
    }

    public function listForCustomer(string $tenantId, string $customerId): array
    {
        $out = [];
        foreach ($this->items as $item) {
            if ($item->tenantId === $tenantId && $item->customerId === $customerId) {
                $out[] = $item;
            }
        }
        return $out;
    }
}
