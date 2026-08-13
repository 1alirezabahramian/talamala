<?php

declare(strict_types=1);

namespace Talamala\Http\Controllers\Auth;

use Talamala\Application\Identity\OtpAuthApplicationService;
use Talamala\Domain\Tenant\Tenant;

/**
 * Stage 2 — customer OTP endpoints.
 * Tenant must already be resolved by ResolveTenantMiddleware.
 */
final class CustomerOtpController
{
    public function __construct(
        private readonly OtpAuthApplicationService $otp,
    ) {}

    /**
     * POST /v1/auth/customer/otp/request
     * Body: { mobile, purpose }
     */
    public function requestOtp(Tenant $tenant, array $body, string $correlationId): array
    {
        $mobile = (string) ($body['mobile'] ?? '');
        $purpose = (string) ($body['purpose'] ?? 'login');

        if ($mobile === '') {
            return $this->json(422, ['error' => 'mobile_required']);
        }

        try {
            $challenge = $this->otp->requestOtp($tenant->id, $mobile, $purpose, $correlationId);
        } catch (\InvalidArgumentException $e) {
            return $this->json(422, ['error' => 'invalid_purpose', 'message' => $e->getMessage()]);
        }

        return $this->json(200, [
            'challenge_id' => $challenge->id,
            'expires_at' => $challenge->expiresAt->format(\DateTimeInterface::ATOM),
            'purpose' => $challenge->purpose,
        ]);
    }

    /**
     * POST /v1/auth/customer/otp/verify
     * Body: { challenge_id, code }
     */
    public function verifyOtp(Tenant $tenant, array $body, string $correlationId): array
    {
        $challengeId = (string) ($body['challenge_id'] ?? '');
        $code = (string) ($body['code'] ?? '');

        if ($challengeId === '' || $code === '') {
            return $this->json(422, ['error' => 'challenge_and_code_required']);
        }

        $result = $this->otp->verifyOtp($tenant->id, $challengeId, $code, $correlationId);

        if (!$result->success) {
            return $this->json(401, [
                'error' => $result->errorCode,
                'message' => $result->errorMessage,
            ]);
        }

        if ($result->requiresRegistration) {
            return $this->json(200, [
                'status' => 'registration_required',
                'message' => 'Mobile verified; complete registration',
            ]);
        }

        return $this->json(200, [
            'status' => 'authenticated',
            'access_token' => $result->sessionToken,
            'customer_id' => $result->customerId,
            'access_status' => $result->accessStatus?->value,
        ]);
    }

    private function json(int $status, array $body): array
    {
        return ['status' => $status, 'body' => $body];
    }
}
