<?php

declare(strict_types=1);

namespace Talamala\Domain\Identity;

interface CustomerRepository
{
    public function findById(string $tenantId, string $customerId): ?Customer;

    public function findByMobile(string $tenantId, string $mobile): ?Customer;

    public function save(Customer $customer): void;

    /** @return list<Customer> */
    public function listPendingRegistration(string $tenantId, int $limit = 50): array;

    /**
     * Staff/admin — tenant-scoped customer directory (no balances).
     * @return list<Customer>
     */
    public function listForTenant(string $tenantId, int $limit = 100): array;
}
