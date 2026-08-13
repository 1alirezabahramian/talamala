<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Sms;

use Talamala\Integrations\Sms\SmsOtpSender;
use Talamala\Integrations\Sms\SmsSendResult;

/**
 * Dev/test double. Never used in production.
 * Does not send real SMS; records last payload for assertions.
 */
final class FakeSmsOtpSender implements SmsOtpSender
{
    /** @var list<array{tenantId:string,mobile:string,templateId:int,parameters:array}> */
    public array $sent = [];

    public function sendVerify(string $tenantId, string $mobile, int $templateId, array $parameters): SmsSendResult
    {
        $this->sent[] = compact('tenantId', 'mobile', 'templateId', 'parameters');
        return new SmsSendResult(true, messageId: 'fake-' . count($this->sent));
    }
}
