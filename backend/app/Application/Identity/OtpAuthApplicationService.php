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
 * Challenges persisted to temp file so PHP built-in server multi-request works locally.
 * Production should use secure store + rate limits (file store is skeleton-only).
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
    ) {
        $this->loadChallenges();
    }

    public function seedExistingCustomer(string $tenantId, string $mobile, string $customerId): void
    {
        $this->knownCustomers[$tenantId . ':' . $mobile] = [
            'customerId' => $customerId,
            'access' => 'active',
        ];
    }

    public function requestOtp(string $tenantId, string $mobile, string $purpose, string $correlationId): OtpChallenge
    {
        $this->loadChallenges();
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
        $this->saveChallenges();

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

        return $challenge;
    }

    public function verifyOtp(
        string $tenantId,
        string $challengeId,
        string $code,
        string $correlationId,
    ): AuthResult {
        $this->loadChallenges();
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
        $this->saveChallenges();

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
        $this->saveChallenges();

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

    private function storePath(): string
    {
        return rtrim(sys_get_temp_dir(), '/') . '/talamala-otp-challenges.json';
    }

    private function loadChallenges(): void
    {
        $path = $this->storePath();
        if (!is_file($path)) {
            return;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return;
        }
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $loaded = [];
        foreach ($data as $id => $row) {
            if (!is_array($row) || !isset($row['tenantId'], $row['mobile'], $row['purpose'], $row['codeHash'], $row['expiresAt'])) {
                continue;
            }
            try {
                $expires = new \DateTimeImmutable((string) $row['expiresAt'], new \DateTimeZone('UTC'));
            } catch (\Throwable) {
                continue;
            }
            $c = new OtpChallenge(
                id: (string) $id,
                tenantId: (string) $row['tenantId'],
                mobile: (string) $row['mobile'],
                purpose: (string) $row['purpose'],
                codeHash: (string) $row['codeHash'],
                expiresAt: $expires,
                attempts: (int) ($row['attempts'] ?? 0),
                maxAttempts: (int) ($row['maxAttempts'] ?? self::MAX_ATTEMPTS),
            );
            if (!$c->isExpired($now)) {
                $loaded[(string) $id] = $c;
            }
        }
        $this->challenges = $loaded;
    }

    private function saveChallenges(): void
    {
        $out = [];
        foreach ($this->challenges as $id => $c) {
            $out[$id] = [
                'tenantId' => $c->tenantId,
                'mobile' => $c->mobile,
                'purpose' => $c->purpose,
                'codeHash' => $c->codeHash,
                'expiresAt' => $c->expiresAt->format(\DateTimeInterface::ATOM),
                'attempts' => $c->attempts,
                'maxAttempts' => $c->maxAttempts,
            ];
        }
        @file_put_contents($this->storePath(), json_encode($out, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
