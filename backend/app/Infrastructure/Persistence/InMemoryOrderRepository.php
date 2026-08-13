<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence;

use Talamala\Domain\Order\Order;
use Talamala\Domain\Order\OrderRepository;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[$order->tenantId . ':' . $order->id] = $order;
    }

    public function findById(string $tenantId, string $orderId): ?Order
    {
        return $this->orders[$tenantId . ':' . $orderId] ?? null;
    }

    public function listForCustomer(string $tenantId, string $customerId, int $limit = 50): array
    {
        $out = [];
        foreach ($this->orders as $o) {
            if ($o->tenantId === $tenantId && $o->customerId === $customerId) {
                $out[] = $o;
                if (count($out) >= $limit) {
                    break;
                }
            }
        }
        return $out;
    }
}
