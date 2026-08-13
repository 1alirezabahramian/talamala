<?php

declare(strict_types=1);

namespace Talamala\Domain\Custody;

interface CustodyRepository
{
    public function save(CustodyItem $item): void;

    public function findById(string $tenantId, string $id): ?CustodyItem;

    /** @return list<CustodyItem> */
    public function listForCustomer(string $tenantId, string $customerId): array;
}
