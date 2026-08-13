<?php

declare(strict_types=1);

namespace Talamala\Application\Custody;

use Talamala\Domain\Audit\AuditEvent;
use Talamala\Domain\Audit\AuditLogger;
use Talamala\Domain\Custody\CustodyItem;
use Talamala\Domain\Custody\CustodyRepository;
use Talamala\Domain\Custody\CustodyStatus;

final class CustodyApplicationService
{
    public function __construct(
        private readonly CustodyRepository $repo,
        private readonly AuditLogger $audit,
    ) {}

    public function receive(
        string $tenantId,
        string $customerId,
        string $description,
        string $weightGrams,
        ?string $fineness,
        string $actorStaffId,
        string $correlationId,
        ?string $barcodeRef = null,
    ): CustodyItem {
        $item = new CustodyItem(
            id: bin2hex(random_bytes(12)),
            tenantId: $tenantId,
            customerId: $customerId,
            description: $description,
            weightGrams: $weightGrams,
            fineness: $fineness,
            status: CustodyStatus::Held,
            receivedAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            barcodeRef: $barcodeRef,
        );
        $this->repo->save($item);
        $this->audit->log(new AuditEvent(
            id: bin2hex(random_bytes(8)),
            tenantId: $tenantId,
            actorId: $actorStaffId,
            actorType: 'staff',
            action: 'custody.receive',
            targetType: 'custody_item',
            targetId: $item->id,
            reason: null,
            correlationId: $correlationId,
            metadata: ['weight' => $weightGrams],
            occurredAt: $item->receivedAt,
        ));
        return $item;
    }

    public function markReady(string $tenantId, string $itemId, string $staffId, string $correlationId): CustodyItem
    {
        $item = $this->repo->findById($tenantId, $itemId);
        if ($item === null) {
            throw new \RuntimeException('Custody item not found');
        }
        $updated = $item->markReady(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->repo->save($updated);
        $this->audit->log(new AuditEvent(
            id: bin2hex(random_bytes(8)),
            tenantId: $tenantId,
            actorId: $staffId,
            actorType: 'staff',
            action: 'custody.ready',
            targetType: 'custody_item',
            targetId: $itemId,
            reason: null,
            correlationId: $correlationId,
            metadata: [],
            occurredAt: $updated->readyAt ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        ));
        return $updated;
    }

    public function deliver(string $tenantId, string $itemId, string $staffId, string $correlationId): CustodyItem
    {
        $item = $this->repo->findById($tenantId, $itemId);
        if ($item === null) {
            throw new \RuntimeException('Custody item not found');
        }
        $updated = $item->markDelivered(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->repo->save($updated);
        $this->audit->log(new AuditEvent(
            id: bin2hex(random_bytes(8)),
            tenantId: $tenantId,
            actorId: $staffId,
            actorType: 'staff',
            action: 'custody.delivered',
            targetType: 'custody_item',
            targetId: $itemId,
            reason: null,
            correlationId: $correlationId,
            metadata: [],
            occurredAt: $updated->deliveredAt ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        ));
        return $updated;
    }
}
