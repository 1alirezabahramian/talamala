<?php

declare(strict_types=1);

namespace Talamala\Domain\Identity;

/**
 * Customer authentication is mobile + OTP only.
 * No customer password login.
 */
interface CustomerAuthService
{
    /**
     * Start OTP challenge for login or registration.
     * Must be rate-limited and tenant-scoped.
     */
    public function requestOtp(string $tenantId, string $mobile, string $purpose): OtpChallenge;

    /**
     * Verify OTP and return authenticated session context.
     * On success: existing customer → session; new mobile → registration path.
     */
    public function verifyOtp(
        string $tenantId,
        string $challengeId,
        string $code,
    ): AuthResult;
}
