<?php

declare(strict_types=1);

namespace Talamala\Integrations\Sms;

/**
 * Abstraction over SMS.ir (or other provider).
 * Tenant templates/credentials loaded from encrypted tenant settings.
 * Never log OTP plaintext or API keys.
 */
interface SmsOtpSender
{
    /**
     * @param array<string, string> $parameters template parameters
     */
    public function sendVerify(string $tenantId, string $mobile, int $templateId, array $parameters): SmsSendResult;
}
