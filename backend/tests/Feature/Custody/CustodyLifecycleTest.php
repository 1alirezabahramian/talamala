<?php

declare(strict_types=1);

namespace Talamala\Tests\Feature\Custody;

use PHPUnit\Framework\TestCase;
use Talamala\Application\Custody\CustodyApplicationService;
use Talamala\Domain\Custody\CustodyStatus;
use Talamala\Infrastructure\Persistence\InMemoryAuditLogger;
use Talamala\Infrastructure\Persistence\InMemoryCustodyRepository;

final class CustodyLifecycleTest extends TestCase
{
    public function testReceiveReadyDeliver(): void
    {
        $svc = new CustodyApplicationService(
            new InMemoryCustodyRepository(),
            new InMemoryAuditLogger(),
        );

        $item = $svc->receive('t1', 'cust1', 'انگشتر امانت', '3.250', '750', 'staff1', 'c1');
        $this->assertSame(CustodyStatus::Held, $item->status);

        $ready = $svc->markReady('t1', $item->id, 'staff1', 'c2');
        $this->assertSame(CustodyStatus::ReadyForPickup, $ready->status);

        $delivered = $svc->deliver('t1', $item->id, 'staff1', 'c3');
        $this->assertSame(CustodyStatus::Delivered, $delivered->status);
    }

    public function testCannotDeliverFromHeld(): void
    {
        $svc = new CustodyApplicationService(
            new InMemoryCustodyRepository(),
            new InMemoryAuditLogger(),
        );
        $item = $svc->receive('t1', 'cust1', 'x', '1', null, 's', 'c');
        $this->expectException(\DomainException::class);
        $svc->deliver('t1', $item->id, 's', 'c2');
    }
}
