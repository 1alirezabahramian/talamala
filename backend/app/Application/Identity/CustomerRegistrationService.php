<?php

declare(strict_types=1);

namespace Talamala\Application\Identity;

use Talamala\Domain\Audit\AuditEvent;
use Talamala\Domain\Audit\AuditLogger;
use Talamala\Domain\Identity\Customer;
use Talamala\Domain\Identity\CustomerAccessStatus;
use Talamala\Domain\Identity\CustomerRepository;
use Talamala\Integrations\Jibit\JibitIdentityClient;

/**
 * Stage 2 registration completion after OTP.
 * Jibit match is verification evidence, NOT automatic approval.
 * Kimia account create remains BLOCKED until write ground truth + credentials.
 */
final class CustomerRegistrationService
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly JibitIdentityClient $jibit,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param array{mobile:string,national_code:string,full_name:string} $data
     */
    public function completeRegistration(
        string $tenantId,
        array $data,
        string $correlationId,
        bool $requireJibitMatch = true,
    ): RegistrationResult {
        $mobile = $this->normalizeMobile($data['mobile'] ?? '');
        $nationalCode = trim($data['national_code'] ?? '');
        $fullName = trim($data['full_name'] ?? '');

        if ($mobile === '' || $nationalCode === '' || $fullName === '') {
            return RegistrationResult::fail('validation', 'mobile, national_code, full_name required');
        }

        if ($this->customers->findByMobile($tenantId, $mobile) !== null) {
            return RegistrationResult::fail('already_registered', 'Customer already exists');
        }

        $jibitRef = null;
        if ($requireJibitMatch) {
            $match = $this->jibit->matchNationalCodeWithMobile($nationalCode, $mobile);
            if (!$match->matched) {
                $this->audit->log($this->event($tenantId, null, 'registration.jibit_mismatch', $correlationId, [
                    'error' => $match->errorCode,
                ]));
                return RegistrationResult::fail('jibit_mismatch', 'Identity verification failed');
            }
            $jibitRef = $match->providerReference;
        }

        // Default: Limited until staff approval (policy-driven; can be auto-active later)
        $customer = new Customer(
            id: bin2hex(random_bytes(12)),
            tenantId: $tenantId,
            mobile: $mobile,
            nationalCode: $nationalCode,
            fullName: $fullName,
            accessStatus: CustomerAccessStatus::Limited,
            kimiaAccountId: null, // Kimia create BLOCKED — bind later
            createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
        $this->customers->save($customer);

        $this->audit->log($this->event($tenantId, $customer->id, 'registration.completed', $correlationId, [
            'jibit_ref' => $jibitRef,
            'access' => $customer->accessStatus->value,
        ]));

        return RegistrationResult::ok($customer);
    }

    public function bindKimiaAccount(
        string $tenantId,
        string $customerId,
        int $kimiaAccountId,
        string $correlationId,
    ): RegistrationResult {
        $customer = $this->customers->findById($tenantId, $customerId);
        if ($customer === null) {
            return RegistrationResult::fail('not_found', 'Customer not found');
        }
        $updated = $customer->withKimiaBinding($kimiaAccountId);
        $this->customers->save($updated);
        $this->audit->log($this->event($tenantId, $customerId, 'customer.kimia_bound', $correlationId, [
            'kimia_account_id' => $kimiaAccountId,
        ]));
        return RegistrationResult::ok($updated);
    }

    public function approveCustomer(
        string $tenantId,
        string $customerId,
        string $staffId,
        string $correlationId,
    ): RegistrationResult {
        $customer = $this->customers->findById($tenantId, $customerId);
        if ($customer === null) {
            return RegistrationResult::fail('not_found', 'Customer not found');
        }
        $updated = new Customer(
            $customer->id,
            $customer->tenantId,
            $customer->mobile,
            $customer->nationalCode,
            $customer->fullName,
            CustomerAccessStatus::Active,
            $customer->kimiaAccountId,
            $customer->createdAt,
            new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
        $this->customers->save($updated);
        $this->audit->log($this->event($tenantId, $customerId, 'customer.approved', $correlationId, [
            'staff_id' => $staffId,
        ]));
        return RegistrationResult::ok($updated);
    }

    private function event(
        string $tenantId,
        ?string $customerId,
        string $action,
        string $correlationId,
        array $metadata,
    ): AuditEvent {
        return new AuditEvent(
            id: bin2hex(random_bytes(8)),
            tenantId: $tenantId,
            actorId: $customerId,
            actorType: 'customer',
            action: $action,
            targetType: 'customer',
            targetId: $customerId,
            reason: null,
            correlationId: $correlationId,
            metadata: $metadata,
            occurredAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    private function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }
        return $digits;
    }
}
