<?php

declare(strict_types=1);

namespace Talamala\Tests\Feature\Identity;

use PHPUnit\Framework\TestCase;
use Talamala\Application\Identity\OtpAuthApplicationService;
use Talamala\Domain\Tenant\Tenant;
use Talamala\Infrastructure\Persistence\InMemoryAuditLogger;
use Talamala\Infrastructure\Persistence\InMemoryTenantResolver;
use Talamala\Infrastructure\Sms\FakeSmsOtpSender;

final class OtpAuthFlowTest extends TestCase
{
    public function testExistingCustomerLoginViaOtp(): void
    {
        $sms = new FakeSmsOtpSender();
        $audit = new InMemoryAuditLogger();
        $svc = new OtpAuthApplicationService($sms, $audit);

        $tenantId = 'tenant-a';
        $mobile = '09121234567';
        $svc->seedExistingCustomer($tenantId, $mobile, 'cust-1');

        $challenge = $svc->requestOtp($tenantId, $mobile, 'login', 'corr-1');
        $this->assertNotEmpty($challenge->id);
        $this->assertCount(1, $sms->sent);
        $code = $sms->sent[0]['parameters']['Code'];

        $result = $svc->verifyOtp($tenantId, $challenge->id, $code, 'corr-1');
        $this->assertTrue($result->success);
        $this->assertFalse($result->requiresRegistration);
        $this->assertSame('cust-1', $result->customerId);
        $this->assertNotNull($result->sessionToken);
    }

    public function testUnknownMobileNeedsRegistration(): void
    {
        $sms = new FakeSmsOtpSender();
        $audit = new InMemoryAuditLogger();
        $svc = new OtpAuthApplicationService($sms, $audit);

        $challenge = $svc->requestOtp('tenant-a', '09120000000', 'registration', 'corr-2');
        $code = $sms->sent[0]['parameters']['Code'];
        $result = $svc->verifyOtp('tenant-a', $challenge->id, $code, 'corr-2');

        $this->assertTrue($result->success);
        $this->assertTrue($result->requiresRegistration);
    }

    public function testWrongCodeFails(): void
    {
        $sms = new FakeSmsOtpSender();
        $svc = new OtpAuthApplicationService($sms, new InMemoryAuditLogger());
        $challenge = $svc->requestOtp('tenant-a', '09121111111', 'login', 'corr-3');
        $result = $svc->verifyOtp('tenant-a', $challenge->id, '000000', 'corr-3');
        $this->assertFalse($result->success);
        $this->assertSame('otp_invalid', $result->errorCode);
    }

    public function testTenantIsolationOnChallenge(): void
    {
        $sms = new FakeSmsOtpSender();
        $svc = new OtpAuthApplicationService($sms, new InMemoryAuditLogger());
        $challenge = $svc->requestOtp('tenant-a', '09122222222', 'login', 'corr-4');
        $code = $sms->sent[0]['parameters']['Code'];
        $result = $svc->verifyOtp('tenant-b', $challenge->id, $code, 'corr-4');
        $this->assertFalse($result->success);
        $this->assertSame('otp_not_found', $result->errorCode);
    }
}
