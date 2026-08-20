<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence;

use Talamala\Domain\Identity\Customer;
use Talamala\Domain\Identity\CustomerAccessStatus;
use Talamala\Domain\Identity\CustomerRepository;

final class InMemoryCustomerRepository implements CustomerRepository
{
    /** @var array<string, Customer> key = tenantId:id */
    private array $byId = [];

    /** @var array<string, string> key = tenantId:mobile → customerId */
    private array $byMobile = [];

    public function findById(string $tenantId, string $customerId): ?Customer
    {
        return $this->byId[$tenantId . ':' . $customerId] ?? null;
    }

    public function findByMobile(string $tenantId, string $mobile): ?Customer
    {
        $id = $this->byMobile[$tenantId . ':' . $mobile] ?? null;
        if ($id === null) {
            return null;
        }
        return $this->findById($tenantId, $id);
    }

    public function save(Customer $customer): void
    {
        $this->byId[$customer->tenantId . ':' . $customer->id] = $customer;
        $this->byMobile[$customer->tenantId . ':' . $customer->mobile] = $customer->id;
    }

    public function listPendingRegistration(string $tenantId, int $limit = 50): array
    {
        $out = [];
        foreach ($this->byId as $c) {
            if ($c->tenantId === $tenantId && $c->accessStatus === CustomerAccessStatus::Limited) {
                $out[] = $c;
                if (count($out) >= $limit) {
                    break;
                }
            }
        }
        return $out;
    }

    public function listForTenant(string $tenantId, int $limit = 100): array
    {
        $out = [];
        foreach ($this->byId as $c) {
            if ($c->tenantId === $tenantId) {
                $out[] = $c;
                if (count($out) >= $limit) {
                    break;
                }
            }
        }
        return $out;
    }
}
