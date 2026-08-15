<?php

declare(strict_types=1);

namespace Talamala\Http;

use Talamala\Application\Custody\CustodyApplicationService;
use Talamala\Application\Identity\CustomerRegistrationService;
use Talamala\Application\Identity\OtpAuthApplicationService;
use Talamala\Application\Identity\StaffAuthApplicationService;
use Talamala\Application\Kimia\CustomerFinancialReadService;
use Talamala\Application\Order\OrderApplicationService;
use Talamala\Domain\Quote\Quote;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteSide;
use Talamala\Domain\Quote\QuoteStatus;
use Talamala\Domain\Session\SessionRecord;
use Talamala\Domain\Tenant\Tenant;
use Talamala\Domain\Tenant\TenantResolver;
use Talamala\Http\Controllers\Admin\RegistrationQueueController;
use Talamala\Http\Controllers\Auth\CustomerOtpController;
use Talamala\Http\Controllers\Auth\StaffAuthController;
use Talamala\Http\Controllers\Customer\CustomerAssetsController;
use Talamala\Http\Controllers\HealthController;
use Talamala\Infrastructure\Persistence\InMemoryAuditLogger;
use Talamala\Infrastructure\Persistence\InMemoryCustodyRepository;
use Talamala\Infrastructure\Persistence\InMemoryCustomerRepository;
use Talamala\Infrastructure\Persistence\InMemoryIdempotencyRegistry;
use Talamala\Infrastructure\Persistence\InMemoryOrderRepository;
use Talamala\Infrastructure\Persistence\InMemoryQuoteRepository;
use Talamala\Infrastructure\Persistence\InMemorySessionStore;
use Talamala\Infrastructure\Persistence\InMemoryTenantResolver;
use Talamala\Infrastructure\Sms\FakeSmsOtpSender;
use Talamala\Integrations\Jibit\FakeJibitIdentityClient;
use Talamala\Integrations\Kimia\FakeKimiaReadClient;
use Talamala\Infrastructure\Security\InMemoryRateLimiter;
use Talamala\Infrastructure\Logging\StructuredLogger;

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
    public readonly StaffAuthApplicationService $staffAuth;
    public readonly OrderApplicationService $orders;
    public readonly InMemoryQuoteRepository $quotes;
    public readonly InMemoryOrderRepository $orderRepo;
    public readonly InMemorySessionStore $sessions;
    public readonly InMemoryRateLimiter $otpRateLimiter;
    public readonly StructuredLogger $log;
    public readonly InMemoryAuditLogger $audit;
    public readonly InMemoryCustodyRepository $custodyRepo;

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
        // Second tenant for adversarial isolation tests
        $this->tenants->register(new Tenant(
            id: '00000000-0000-0000-0000-000000000002',
            slug: 'other',
            primaryHost: 'other.local',
            isActive: true,
            isVerified: true,
        ));

        $this->sms = new FakeSmsOtpSender();
        $this->otp = new OtpAuthApplicationService($this->sms, $this->audit);

        $this->staffAuth = new StaffAuthApplicationService($this->audit);
        // Demo staff: must change password on first login
        $this->staffAuth->bootstrapStaff(
            '00000000-0000-0000-0000-000000000001',
            'staff-demo-1',
            'operator',
            'ChangeMe-Now-1',
            true,
        );

        $this->customers = new InMemoryCustomerRepository();
        $jibit = new FakeJibitIdentityClient();
        // Dev convenience: allow common test national/mobile pair
        $jibit->allowMatch('0012345678', '09121234567');
        $this->registration = new CustomerRegistrationService($this->customers, $jibit, $this->audit);

        $this->kimia = new FakeKimiaReadClient();
        $this->financialRead = new CustomerFinancialReadService($this->kimia);

        $this->custodyRepo = new InMemoryCustodyRepository();
        $this->custody = new CustodyApplicationService($this->custodyRepo, $this->audit);

        $this->quotes = new InMemoryQuoteRepository();
        $this->orderRepo = new InMemoryOrderRepository();
        $this->orders = new OrderApplicationService(
            $this->quotes,
            $this->orderRepo,
            new InMemoryIdempotencyRegistry(),
            $this->audit,
        );
        $this->sessions = new InMemorySessionStore();
        $this->otpRateLimiter = new InMemoryRateLimiter(maxAttempts: 5, windowSeconds: 300);
        $this->log = new StructuredLogger();
    }

    /** Issue a short-lived skeleton session (replace with signed JWT/DB later). */
    public function issueSession(string $tenantId, string $subjectType, string $subjectId, int $ttlSeconds = 3600): string
    {
        $token = bin2hex(random_bytes(24));
        $this->sessions->put(new SessionRecord(
            token: $token,
            tenantId: $tenantId,
            subjectType: $subjectType,
            subjectId: $subjectId,
            expiresAt: (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify("+{$ttlSeconds} seconds"),
        ));
        return $token;
    }

    public function sessionFromAuthHeader(array $headers): ?SessionRecord
    {
        $auth = $headers['authorization'] ?? '';
        if (!preg_match('/^Bearer\s+(\S+)/i', $auth, $m)) {
            return null;
        }
        $session = $this->sessions->get($m[1]);
        if ($session === null) {
            return null;
        }
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return $session->isValid($now) ? $session : null;
    }

    /**
     * production | staging | local (default local allows skeleton header fallbacks)
     */
    public static function environment(): string
    {
        $env = getenv('TALAMALA_ENV') ?: getenv('APP_ENV') ?: 'local';
        return strtolower(trim((string) $env));
    }

    public static function isProduction(): bool
    {
        return self::environment() === 'production';
    }

    /**
     * Prefer Bearer customer session; X-Customer-Id only outside production.
     * @return array{0:?string,1:?array} [customerId, errorResponse]
     */
    private function resolveCustomerId(array $headers): array
    {
        $session = $this->sessionFromAuthHeader($headers);
        if ($session !== null) {
            if ($session->subjectType !== 'customer') {
                return [null, ['status' => 403, 'body' => ['error' => 'customer_session_required']]];
            }
            return [$session->subjectId, null];
        }
        if (!self::isProduction()) {
            $fallback = $headers['x-customer-id'] ?? '';
            if ($fallback !== '') {
                return [$fallback, null];
            }
        }
        return [null, ['status' => 401, 'body' => [
            'error' => 'unauthorized',
            'message' => self::isProduction() ? 'Bearer required' : 'Bearer or X-Customer-Id required',
        ]]];
    }

    /**
     * Prefer Bearer staff session; X-Staff-Id only outside production.
     * @return array{0:?string,1:?array}
     */
    private function resolveStaffId(array $headers): array
    {
        $session = $this->sessionFromAuthHeader($headers);
        if ($session !== null) {
            if ($session->subjectType !== 'staff') {
                return [null, ['status' => 403, 'body' => ['error' => 'staff_session_required']]];
            }
            return [$session->subjectId, null];
        }
        if (!self::isProduction()) {
            $fallback = $headers['x-staff-id'] ?? '';
            if ($fallback !== '') {
                return [$fallback, null];
            }
        }
        return [null, ['status' => 401, 'body' => [
            'error' => 'unauthorized',
            'message' => self::isProduction() ? 'Bearer required' : 'Bearer or X-Staff-Id required',
        ]]];
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
            $this->log->warning('tenant.unresolved', ['host' => $host, 'message' => $e->getMessage()]);
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
            $mobile = (string) ($body['mobile'] ?? '');
            $rlKey = $tenant->id . ':otp:' . preg_replace('/\D+/', '', $mobile);
            $rl = $this->otpRateLimiter->hit($rlKey);
            if (!$rl['allowed']) {
                $this->log->warning('otp.rate_limited', ['tenant_id' => $tenant->id, 'correlation_id' => $correlationId]);
                return [
                    'status' => 429,
                    'body' => [
                        'error' => 'rate_limited',
                        'retry_after' => $rl['retry_after'],
                    ],
                    'headers' => ['Retry-After' => (string) $rl['retry_after']],
                ];
            }
            return (new CustomerOtpController($this->otp))->requestOtp($tenant, $body, $correlationId);
        }
        if ($method === 'POST' && $path === '/v1/auth/customer/otp/verify') {
            return (new CustomerOtpController($this->otp))->verifyOtp($tenant, $body, $correlationId);
        }

        // Staff auth
        if ($method === 'POST' && $path === '/v1/auth/staff/login') {
            $resp = (new StaffAuthController($this->staffAuth))->login($tenant, $body, $correlationId);
            if (($resp['status'] ?? 0) === 200 && !empty($resp['body']['staff_id'])) {
                $token = $this->issueSession($tenant->id, 'staff', (string) $resp['body']['staff_id']);
                $resp['body']['access_token'] = $token;
                $resp['body']['token_type'] = 'Bearer';
            }
            return $resp;
        }
        if ($method === 'POST' && $path === '/v1/auth/staff/password/rotate') {
            $staffId = $headers['x-staff-id'] ?? '';
            if ($staffId === '') {
                return ['status' => 401, 'body' => ['error' => 'staff_id_required']];
            }
            return (new StaffAuthController($this->staffAuth))->rotatePassword($tenant, $staffId, $body, $correlationId);
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
            [$customerId, $err] = $this->resolveCustomerId($headers);
            if ($err !== null) {
                return $err;
            }
            return (new CustomerAssetsController($this->customers, $this->financialRead))
                ->assets($tenant, $customerId);
        }

        // Admin registration queue
        if ($method === 'GET' && $path === '/v1/admin/registrations') {
            [$staffId, $err] = $this->resolveStaffId($headers);
            if ($err !== null) {
                return $err;
            }
            return (new RegistrationQueueController($this->customers, $this->registration))->index($tenant);
        }
        if ($method === 'POST' && preg_match('#^/v1/admin/registrations/([^/]+)/approve$#', $path, $m)) {
            [$staffId, $err] = $this->resolveStaffId($headers);
            if ($err !== null) {
                return $err;
            }
            return (new RegistrationQueueController($this->customers, $this->registration))
                ->approve($tenant, $m[1], $staffId, $correlationId);
        }

        // Custody ops (staff) — skeleton auth via X-Staff-Id
        if ($method === 'POST' && $path === '/v1/admin/custody/receive') {
            [$staffId, $err] = $this->resolveStaffId($headers);
            if ($err !== null) {
                return $err;
            }
            $customerId = (string) ($body['customer_id'] ?? '');
            $description = (string) ($body['description'] ?? '');
            $weight = (string) ($body['weight_grams'] ?? '');
            $fineness = isset($body['fineness']) ? (string) $body['fineness'] : null;
            if ($customerId === '' || $description === '' || $weight === '') {
                return ['status' => 422, 'body' => ['error' => 'customer_description_weight_required']];
            }
            $item = $this->custody->receive(
                $tenant->id,
                $customerId,
                $description,
                $weight,
                $fineness,
                $staffId,
                $correlationId,
                isset($body['barcode_ref']) ? (string) $body['barcode_ref'] : null,
            );
            return [
                'status' => 201,
                'body' => [
                    'id' => $item->id,
                    'status' => $item->status->value,
                    'weight_grams' => $item->weightGrams,
                ],
            ];
        }
        if ($method === 'POST' && preg_match('#^/v1/admin/custody/([^/]+)/ready$#', $path, $m)) {
            [$staffId, $err] = $this->resolveStaffId($headers);
            if ($err !== null) {
                return $err;
            }
            try {
                $item = $this->custody->markReady($tenant->id, $m[1], $staffId, $correlationId);
            } catch (\Throwable $e) {
                return ['status' => 400, 'body' => ['error' => 'custody_transition_failed', 'message' => $e->getMessage()]];
            }
            return ['status' => 200, 'body' => ['id' => $item->id, 'status' => $item->status->value]];
        }
        if ($method === 'POST' && preg_match('#^/v1/admin/custody/([^/]+)/deliver$#', $path, $m)) {
            [$staffId, $err] = $this->resolveStaffId($headers);
            if ($err !== null) {
                return $err;
            }
            try {
                $item = $this->custody->deliver($tenant->id, $m[1], $staffId, $correlationId);
            } catch (\Throwable $e) {
                return ['status' => 400, 'body' => ['error' => 'custody_transition_failed', 'message' => $e->getMessage()]];
            }
            return ['status' => 200, 'body' => ['id' => $item->id, 'status' => $item->status->value]];
        }
        if ($method === 'GET' && $path === '/v1/customer/custody') {
            [$customerId, $err] = $this->resolveCustomerId($headers);
            if ($err !== null) {
                return $err;
            }
            $items = $this->custodyRepo->listForCustomer($tenant->id, $customerId);
            $out = array_map(static fn ($i) => [
                'id' => $i->id,
                'description' => $i->description,
                'weight_grams' => $i->weightGrams,
                'status' => $i->status->value,
            ], $items);
            return ['status' => 200, 'body' => ['items' => $out]];
        }

        // Orders — accept from immutable quote (settlement remains blocked)
        if ($method === 'POST' && $path === '/v1/customer/orders/accept') {
            [$customerId, $err] = $this->resolveCustomerId($headers);
            if ($err !== null) {
                return $err;
            }
            $quoteId = (string) ($body['quote_id'] ?? '');
            $idem = $headers['idempotency-key'] ?? ($body['idempotency_key'] ?? '');
            if ($quoteId === '' || $idem === '') {
                return ['status' => 422, 'body' => ['error' => 'quote_id_and_idempotency_key_required']];
            }
            $result = $this->orders->acceptFromQuote($tenant->id, $customerId, $quoteId, (string) $idem, $correlationId);
            if (!$result->success) {
                return ['status' => 409, 'body' => ['error' => $result->errorCode, 'message' => $result->errorMessage]];
            }
            return [
                'status' => 200,
                'body' => [
                    'order_id' => $result->order->id,
                    'status' => $result->order->status->value,
                    'from_idempotency_cache' => $result->fromIdempotencyCache,
                    'settlement' => 'blocked_by_ground_truth',
                ],
            ];
        }
        if ($method === 'GET' && $path === '/v1/customer/orders') {
            [$customerId, $err] = $this->resolveCustomerId($headers);
            if ($err !== null) {
                return $err;
            }
            $list = $this->orderRepo->listForCustomer($tenant->id, $customerId);
            $out = array_map(static fn ($o) => [
                'order_id' => $o->id,
                'quote_id' => $o->quoteId,
                'status' => $o->status->value,
                'quantity' => $o->quantity,
                'total_rial' => $o->totalRial,
            ], $list);
            return ['status' => 200, 'body' => ['items' => $out]];
        }

        // Dev-only helpers (never enable in production)
        if (!self::isProduction() && ($headers['x-talamala-dev'] ?? '') === '1') {
            if ($method === 'GET' && $path === '/v1/dev/last-otp') {
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
            // Seed a manual open quote for order smoke (prices are test fixtures, not live feed)
            if ($method === 'POST' && $path === '/v1/dev/seed-quote') {
                $customerId = (string) ($body['customer_id'] ?? '');
                if ($customerId === '') {
                    return ['status' => 422, 'body' => ['error' => 'customer_id_required']];
                }
                $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
                $quote = new Quote(
                    id: 'q-' . bin2hex(random_bytes(6)),
                    tenantId: $tenant->id,
                    customerId: $customerId,
                    side: QuoteSide::Buy,
                    asset: QuoteAsset::Gold18,
                    quantity: (string) ($body['quantity'] ?? '1.000'),
                    unitPriceRial: (string) ($body['unit_price_rial'] ?? '350000000'),
                    totalRial: (string) ($body['total_rial'] ?? '350000000'),
                    issuedAt: $now,
                    expiresAt: $now->modify('+5 minutes'),
                    status: QuoteStatus::Open,
                    priceSourceRef: 'dev-manual-fixture',
                );
                $this->quotes->save($quote);
                return [
                    'status' => 201,
                    'body' => [
                        'quote_id' => $quote->id,
                        'expires_at' => $quote->expiresAt->format(\DateTimeInterface::ATOM),
                        'note' => 'Fixture only — not a live price provider',
                    ],
                ];
            }
            if ($method === 'POST' && $path === '/v1/dev/session') {
                $type = (string) ($body['subject_type'] ?? 'customer');
                $id = (string) ($body['subject_id'] ?? '');
                if ($id === '' || !in_array($type, ['customer', 'staff'], true)) {
                    return ['status' => 422, 'body' => ['error' => 'subject_type_and_id_required']];
                }
                $token = $this->issueSession($tenant->id, $type, $id);
                return ['status' => 200, 'body' => ['access_token' => $token, 'token_type' => 'Bearer']];
            }
            // Manual Kimia account bind + optional fake balance seed (local vertical only)
            // Does NOT create Kimia customer — account id is supplied by operator/test.
            if ($method === 'POST' && $path === '/v1/dev/bind-kimia') {
                $customerId = (string) ($body['customer_id'] ?? '');
                $kimiaAccountId = (int) ($body['kimia_account_id'] ?? 0);
                if ($customerId === '' || $kimiaAccountId <= 0) {
                    return ['status' => 422, 'body' => ['error' => 'customer_id_and_kimia_account_id_required']];
                }
                $result = $this->registration->bindKimiaAccount(
                    $tenant->id,
                    $customerId,
                    $kimiaAccountId,
                    $correlationId,
                );
                if (!$result->success) {
                    return [
                        'status' => 400,
                        'body' => ['error' => $result->errorCode, 'message' => $result->errorMessage],
                    ];
                }
                // Optional seed for FakeKimia so GET /assets returns non-zero in local demos
                if (isset($body['seed_money_rial']) || isset($body['seed_gold_weight_g'])) {
                    $money = (string) ($body['seed_money_rial'] ?? '0');
                    $weight = (string) ($body['seed_gold_weight_g'] ?? '0');
                    $this->kimia->seedBalance($kimiaAccountId, [
                        [
                            'Weight' => $weight,
                            'Money' => $money,
                            'CurrencyId' => 11,
                            'CurrencySymbol' => 'ریال',
                        ],
                    ]);
                }
                return [
                    'status' => 200,
                    'body' => [
                        'customer_id' => $result->customer->id,
                        'kimia_account_id' => $kimiaAccountId,
                        'kimia_bound' => true,
                        'note' => 'Dev-only bind — not Kimia Write / create account',
                    ],
                ];
            }
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
