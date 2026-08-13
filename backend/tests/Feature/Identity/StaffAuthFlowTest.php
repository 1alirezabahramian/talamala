<?php

declare(strict_types=1);

namespace Talamala\Tests\Feature\Identity;

use PHPUnit\Framework\TestCase;
use Talamala\Application\Identity\StaffAuthApplicationService;
use Talamala\Infrastructure\Persistence\InMemoryAuditLogger;

final class StaffAuthFlowTest extends TestCase
{
    public function testFirstLoginRequiresPasswordChange(): void
    {
        $svc = new StaffAuthApplicationService(new InMemoryAuditLogger());
        $svc->bootstrapStaff('t1', 's1', 'operator1', 'TempPass-12345', true);

        $login = $svc->attemptLogin('t1', 'operator1', 'TempPass-12345', 'c1');
        $this->assertTrue($login->success);
        $this->assertTrue($login->mustChangePassword);

        $rotated = $svc->rotatePassword('t1', 's1', 'TempPass-12345', 'NewStrong-9999', 'c2');
        $this->assertTrue($rotated->success);

        $login2 = $svc->attemptLogin('t1', 'operator1', 'NewStrong-9999', 'c3');
        $this->assertTrue($login2->success);
        $this->assertFalse($login2->mustChangePassword);
    }

    public function testCrossTenantStaffIsolated(): void
    {
        $svc = new StaffAuthApplicationService(new InMemoryAuditLogger());
        $svc->bootstrapStaff('t1', 's1', 'sharedname', 'Pass-AAAA1111', false);

        $fail = $svc->attemptLogin('t2', 'sharedname', 'Pass-AAAA1111', 'c1');
        $this->assertFalse($fail->success);
    }
}
