<?php

declare(strict_types=1);

namespace Talamala\Http\Controllers\Admin;

use Talamala\Application\Identity\CustomerRegistrationService;
use Talamala\Domain\Identity\CustomerRepository;
use Talamala\Domain\Tenant\Tenant;

/**
 * Backoffice registration review queue.
 */
final class RegistrationQueueController
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly CustomerRegistrationService $registration,
    ) {}

    /** GET /v1/admin/registrations */
    public function index(Tenant $tenant): array
    {
        $pending = $this->customers->listPendingRegistration($tenant->id);
        $items = array_map(static function ($c) {
            return [
                'customer_id' => $c->id,
                'mobile' => $c->mobile,
                'full_name' => $c->fullName,
                'national_code' => $c->nationalCode,
                'access_status' => $c->accessStatus->value,
                'kimia_bound' => $c->isBoundToKimia(),
                'created_at' => $c->createdAt->format(\DateTimeInterface::ATOM),
            ];
        }, $pending);

        return ['status' => 200, 'body' => ['items' => $items]];
    }

    /** POST /v1/admin/registrations/{id}/approve */
    public function approve(Tenant $tenant, string $customerId, string $staffId, string $correlationId): array
    {
        $result = $this->registration->approveCustomer($tenant->id, $customerId, $staffId, $correlationId);
        if (!$result->success) {
            return ['status' => 400, 'body' => ['error' => $result->errorCode, 'message' => $result->errorMessage]];
        }
        return [
            'status' => 200,
            'body' => [
                'customer_id' => $result->customer->id,
                'access_status' => $result->customer->accessStatus->value,
            ],
        ];
    }
}
