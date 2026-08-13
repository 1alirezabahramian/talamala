<?php

declare(strict_types=1);

namespace Talamala\Application\Identity;

use Talamala\Domain\Audit\AuditEvent;
use Talamala\Domain\Audit\AuditLogger;
use Talamala\Domain\Identity\AuthResult;
use Talamala\Domain\Identity\OtpChallenge;
use Talamala\Integrations\Sms\SmsOtpSender;

/**
 * Stage 2 application service: request + verify OTP.
 * Uses hashed code storage in memory for skeleton; production uses secure store + rate limits.
 */
final class OtpAuthApplicationService
{
    private const OTP_TTL_SECONDS = 120;
    private const MAX_ATTEMPTS = 5;

    /** @var array<string, OtpChallenge> */
    private array $challenges = [];

    /** @var array<string, array{customerId:string,access:string}> mobile index per tenant for "existing" */
    private array $knownCustomers = [];

    public function __construct(
        private readonly SmsOtpSender $sms,
        private readonly AuditLogger $audit,
        private readonly int $otpTemplateId = 1,
    ) {}

    public function seedExistingCustomer(string $tenantId, string $mobile, string $customerId): void
    {
        $this->knownCustomers[$tenantId . ':' . $mobile] = [
            'customerId' => $customerId,
            'access' => 'active',
        ];
    }

    public function requestOtp(string $tenantId, string $mobile, string $purpose, string $correlationId): OtpChallenge
    {
        $mobile = $this->normalizeMobile($mobile);
        if (!in_array($purpose, ['login', 'registration'], true)) {
            throw new \InvalidArgumentException('Invalid OTP purpose');
        }

        $plain = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $id = bin2hex(random_bytes(16));
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $challenge = new OtpChallenge(
            id: $id,
            tenantId: $tenantId,
            mobile: $mobile,
            purpose: $purpose,
            codeHash: password_hash($plain, PASSWORD_DEFAULT),
            expiresAt: $now->modify('+' . self::OTP_TTL_SECONDS . ' seconds'),
            attempts: 0,
            maxAttempts: self::MAX_ATTEMPTS,
        );
        $this->challenges[$id] = $challenge;

        $this->sms->sendVerify($tenantId, $mobile, $this->otpTemplateId, [
            'Code' => $plain,
        ]);

        $this->audit->log(new AuditEvent(
            id: bin2hex(random_bytes(8)),
            tenantId: $tenantId,
            actorId: null,
            actorType: 'system',
            action: 'otp.request',
            targetType: 'mobile',
            targetId: $this->maskMobile($mobile),
            reason: $purpose,
            correlationId: $correlationId,
            metadata: ['challenge_id' => $id],
            occurredAt: $now,
        ));

        // Dev only: attach plain code via metadata is forbidden in production logs.
        // Tests read via reflection or FakeSms last parameters.
        return $challenge;
    }

    public function verifyOtp(
        string $tenantId,
        string $challengeId,
        string $code,
        string $correlationId,
    ): AuthResult {
        $challenge = $this->challenges[$challengeId] ?? null;
        if ($challenge === null || $challenge->tenantId !== $tenantId) {
            return AuthResult::failure('otp_not_found', 'Challenge not found');
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        if ($challenge->isExpired($now)) {
            return AuthResult::failure('otp_expired', 'OTP expired');
        }
        if ($challenge->isExhausted()) {
            return AuthResult::failure('otp_exhausted', 'Too many attempts');
        }

        $challenge = new OtpChallenge(
            $challenge->id,
            $challenge->tenantId,
            $challenge->mobile,
            $challenge->purpose,
            $challenge->codeHash,
            $challenge->expiresAt,
            $challenge->attempts + 1,
            $challenge->maxAttempts,
        );
        $this->challenges[$challengeId] = $challenge;

        if (!password_verify($code, $challenge->codeHash)) {
            $this->audit->log(new AuditEvent(
                id: bin2hex(random_bytes(8)),
                tenantId: $tenantId,
                actorId: null,
                actorType: 'system',
                action: 'otp.verify_failed',
                targetType: 'challenge',
                targetId: $challengeId,
                reason: 'bad_code',
                correlationId: $correlationId,
                metadata: [],
                occurredAt: $now,
            ));
            return AuthResult::failure('otp_invalid', 'Invalid code');
        }

        unset($this->challenges[$challengeId]);

        $key = $tenantId . ':' . $challenge->mobile;
        if (isset($this->knownCustomers[$key])) {
            $c = $this->knownCustomers[$key];
            $token = bin2hex(random_bytes(24));
            $this->audit->log(new AuditEvent(
                id: bin2hex(random_bytes(8)),
                tenantId: $tenantId,
                actorId: $c['customerId'],
                actorType: 'customer',
                action: 'otp.verify_success',
                targetType: 'customer',
                targetId: $c['customerId'],
                reason: 'login',
                correlationId: $correlationId,
                metadata: [],
                occurredAt: $now,
            ));
            return AuthResult::success(
                $token,
                $c['customerId'],
                \Talamala\Domain\Identity\CustomerAccessStatus::from($c['access']),
            );
        }

        return AuthResult::needsRegistration();
    }

    private function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        }
        return $digits;
    }

    private function maskMobile(string $mobile): string
    {
        if (strlen($mobile) < 7) {
            return '***';
        }
        return substr($mobile, 0, 4) . '****' . substr($mobile, -3);
    }
}
