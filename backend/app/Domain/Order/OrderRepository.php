<?php

declare(strict_types=1);

namespace Talamala\Domain\Order;

interface OrderRepository
{
    public function save(Order $order): void;

    public function findById(string $tenantId, string $orderId): ?Order;

    /** @return list<Order> */
    public function listForCustomer(string $tenantId, string $customerId, int $limit = 50): array;
}
