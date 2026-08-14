<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Sms;

use Talamala\Integrations\Sms\SmsOtpSender;
use Talamala\Integrations\Sms\SmsSendResult;

/**
 * Dev/test double. Never used in production.
 * Does not send real SMS; records last payload for assertions.
 * Persists to temp file so PHP built-in multi-request local demo works.
 */
final class FakeSmsOtpSender implements SmsOtpSender
{
    /** @var list<array{tenantId:string,mobile:string,templateId:int,parameters:array}> */
    public array $sent = [];

    public function __construct()
    {
        $this->load();
    }

    public function sendVerify(string $tenantId, string $mobile, int $templateId, array $parameters): SmsSendResult
    {
        $this->load();
        $this->sent[] = compact('tenantId', 'mobile', 'templateId', 'parameters');
        $this->save();
        return new SmsSendResult(true, messageId: 'fake-' . count($this->sent));
    }

    private function path(): string
    {
        return rtrim(sys_get_temp_dir(), '/') . '/talamala-fake-sms.json';
    }

    private function load(): void
    {
        $path = $this->path();
        if (!is_file($path)) {
            return;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return;
        }
        $data = json_decode($raw, true);
        if (is_array($data)) {
            $this->sent = $data;
        }
    }

    private function save(): void
    {
        @file_put_contents($this->path(), json_encode($this->sent, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
