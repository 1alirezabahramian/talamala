<?php

declare(strict_types=1);

namespace Talamala\Http;

use Talamala\Application\Custody\CustodyApplicationService;
use Talamala\Application\Identity\CustomerRegistrationService;
use Talamala\Application\Identity\OtpAuthApplicationService;
use Talamala\Application\Kimia\CustomerFinancialReadService;
use Talamala\Domain\Tenant\Tenant;
use Talamala\Domain\Tenant\TenantResolver;
use Talamala\Http\Controllers\Admin\RegistrationQueueController;
use Talamala\Http\Controllers\Auth\CustomerOtpController;
use Talamala\Http\Controllers\Customer\CustomerAssetsController;
use Talamala\Http\Controllers\HealthController;
use Talamala\Infrastructure\Persistence\InMemoryAuditLogger;
use Talamala\Infrastructure\Persistence\InMemoryCustodyRepository;
use Talamala\Infrastructure\Persistence\InMemoryCustomerRepository;
use Talamala\Infrastructure\Persistence\InMemoryTenantResolver;
use Talamala\Infrastructure\Sms\FakeSmsOtpSender;
use Talamala\Integrations\Jibit\FakeJibitIdentityClient;
use Talamala\Integrations\Kimia\FakeKimiaReadClient;

/**
 * Minimal composition root for Stage 1–3 skeleton.
 * Replace InMemory/Fake bindings with DB/HTTP in later stages.
 */
final class Kernel
{
    public readonly TenantResolver $tenants;
    public readonly FakeSmsOtpSender $sms;
    public readonly OtpAuthApplicationService $otp;
    public readonly CustomerRegistrationService $registration;
    public readonly InMemoryCustomerRepository $customers;
    public readonly CustomerFinancialReadService $financialRead;
    public readonly FakeKimiaReadClient $kimia;
    public readonly CustodyApplicationService $custody;
    public readonly InMemoryAuditLogger $audit;

    public function __construct()
    {
        $this->audit = new InMemoryAuditLogger();
        $this->tenants = new InMemoryTenantResolver();
        $this->tenants->register(new Tenant(
            id: '00000000-0000-0000-0000-000000000001',
            slug: 'demo',
            primaryHost: 'demo.local',
            isActive: true,
            isVerified: true,
        ));

        $this->sms = new FakeSmsOtpSender();
        $this->otp = new OtpAuthApplicationService($this->sms, $this->audit);

        $this->customers = new InMemoryCustomerRepository();
        $jibit = new FakeJibitIdentityClient();
        // Dev convenience: allow common test national/mobile pair
        $jibit->allowMatch('0012345678', '09121234567');
        $this->registration = new CustomerRegistrationService($this->customers, $jibit, $this->audit);

        $this->kimia = new FakeKimiaReadClient();
        $this->financialRead = new CustomerFinancialReadService($this->kimia);

        $this->custody = new CustodyApplicationService(new InMemoryCustodyRepository(), $this->audit);
    }

    /**
     * @return array{status:int,body:array}
     */
    public function handle(string $method, string $path, array $headers, ?array $jsonBody): array
    {
        $correlationId = $headers['x-correlation-id'] ?? bin2hex(random_bytes(8));
        $path = rtrim($path, '/') ?: '/';

        if ($method === 'GET' && ($path === '/healthz' || $path === '/v1/healthz')) {
            return ['status' => 200, 'body' => (new HealthController())->live()];
        }

        $host = $headers['x-talamala-host'] ?? $headers['host'] ?? '';
        try {
            $tenant = $this->tenants->resolveFromHost($host);
        } catch (\Throwable $e) {
            return ['status' => 400, 'body' => ['error' => 'tenant_unresolved', 'message' => $e->getMessage()]];
        }

        if ($method === 'GET' && ($path === '/readyz' || $path === '/v1/readyz')) {
            $ready = (new HealthController())->ready();
            $ready['tenant_id'] = $tenant->id;
            $ready['tenant_slug'] = $tenant->slug;
            return ['status' => 200, 'body' => $ready];
        }

        $body = $jsonBody ?? [];

        // Auth OTP
        if ($method === 'POST' && $path === '/v1/auth/customer/otp/request') {
            return (new CustomerOtpController($this->otp))->requestOtp($tenant, $body, $correlationId);
        }
        if ($method === 'POST' && $path === '/v1/auth/customer/otp/verify') {
            return (new CustomerOtpController($this->otp))->verifyOtp($tenant, $body, $correlationId);
        }

        // Registration (after OTP verify registration_required)
        if ($method === 'POST' && $path === '/v1/auth/customer/register') {
            $result = $this->registration->completeRegistration($tenant->id, $body, $correlationId);
            if (!$result->success) {
                return ['status' => 400, 'body' => ['error' => $result->errorCode, 'message' => $result->errorMessage]];
            }
            return [
                'status' => 201,
                'body' => [
                    'customer_id' => $result->customer->id,
                    'access_status' => $result->customer->accessStatus->value,
                    'kimia_bound' => $result->customer->isBoundToKimia(),
                ],
            ];
        }

        // Customer assets — requires X-Customer-Id header in skeleton (session later)
        if ($method === 'GET' && $path === '/v1/customer/assets') {
            $customerId = $headers['x-customer-id'] ?? '';
            if ($customerId === '') {
                return ['status' => 401, 'body' => ['error' => 'customer_id_required']];
            }
            return (new CustomerAssetsController($this->customers, $this->financialRead))
                ->assets($tenant, $customerId);
        }

        // Admin registration queue
        if ($method === 'GET' && $path === '/v1/admin/registrations') {
            return (new RegistrationQueueController($this->customers, $this->registration))->index($tenant);
        }
        if ($method === 'POST' && preg_match('#^/v1/admin/registrations/([^/]+)/approve$#', $path, $m)) {
            $staffId = $headers['x-staff-id'] ?? 'staff-unknown';
            return (new RegistrationQueueController($this->customers, $this->registration))
                ->approve($tenant, $m[1], $staffId, $correlationId);
        }

        // Dev-only: last OTP code from Fake SMS (never in production)
        if ($method === 'GET' && $path === '/v1/dev/last-otp' && ($headers['x-talamala-dev'] ?? '') === '1') {
            $last = $this->sms->sent[array_key_last($this->sms->sent)] ?? null;
            return [
                'status' => 200,
                'body' => [
                    'mobile' => $last['mobile'] ?? null,
                    'code' => $last['parameters']['Code'] ?? null,
                    'count' => count($this->sms->sent),
                ],
            ];
        }

        return [
            'status' => 404,
            'body' => [
                'error' => 'not_found',
                'path' => $path,
                'tenant' => $tenant->slug,
            ],
        ];
    }
}
