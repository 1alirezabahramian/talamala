<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Persistence\Sqlite;

use PDO;
use Talamala\Domain\Identity\Customer;
use Talamala\Domain\Identity\CustomerAccessStatus;
use Talamala\Domain\Identity\CustomerRepository;

final class SqliteCustomerRepository implements CustomerRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function findById(string $tenantId, string $customerId): ?Customer
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM customers WHERE tenant_id = :t AND id = :id LIMIT 1'
        );
        $st->execute(['t' => $tenantId, 'id' => $customerId]);
        $row = $st->fetch();
        return $row ? $this->map($row) : null;
    }

    public function findByMobile(string $tenantId, string $mobile): ?Customer
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM customers WHERE tenant_id = :t AND mobile = :m LIMIT 1'
        );
        $st->execute(['t' => $tenantId, 'm' => $mobile]);
        $row = $st->fetch();
        return $row ? $this->map($row) : null;
    }

    public function save(Customer $customer): void
    {
        $st = $this->pdo->prepare(<<<'SQL'
INSERT INTO customers (
    id, tenant_id, mobile, national_code, full_name,
    access_status, kimia_account_id, created_at, approved_at
) VALUES (
    :id, :tenant_id, :mobile, :national_code, :full_name,
    :access_status, :kimia_account_id, :created_at, :approved_at
)
ON CONFLICT(id) DO UPDATE SET
    mobile = excluded.mobile,
    national_code = excluded.national_code,
    full_name = excluded.full_name,
    access_status = excluded.access_status,
    kimia_account_id = excluded.kimia_account_id,
    approved_at = excluded.approved_at
SQL);
        $st->execute([
            'id' => $customer->id,
            'tenant_id' => $customer->tenantId,
            'mobile' => $customer->mobile,
            'national_code' => $customer->nationalCode,
            'full_name' => $customer->fullName,
            'access_status' => $customer->accessStatus->value,
            'kimia_account_id' => $customer->kimiaAccountId,
            'created_at' => $customer->createdAt->format(\DateTimeInterface::ATOM),
            'approved_at' => $customer->approvedAt?->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function listPendingRegistration(string $tenantId, int $limit = 50): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM customers WHERE tenant_id = :t AND access_status = :s
             ORDER BY created_at ASC LIMIT :lim'
        );
        $st->bindValue('t', $tenantId);
        $st->bindValue('s', CustomerAccessStatus::Limited->value);
        $st->bindValue('lim', $limit, PDO::PARAM_INT);
        $st->execute();
        $out = [];
        while ($row = $st->fetch()) {
            $out[] = $this->map($row);
        }
        return $out;
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Customer
    {
        return new Customer(
            id: (string) $row['id'],
            tenantId: (string) $row['tenant_id'],
            mobile: (string) $row['mobile'],
            nationalCode: $row['national_code'] !== null ? (string) $row['national_code'] : null,
            fullName: $row['full_name'] !== null ? (string) $row['full_name'] : null,
            accessStatus: CustomerAccessStatus::from((string) $row['access_status']),
            kimiaAccountId: $row['kimia_account_id'] !== null ? (int) $row['kimia_account_id'] : null,
            createdAt: new \DateTimeImmutable((string) $row['created_at']),
            approvedAt: $row['approved_at'] !== null
                ? new \DateTimeImmutable((string) $row['approved_at'])
                : null,
        );
    }
}
