<?php

declare(strict_types=1);

namespace Talamala\Domain\Order;

enum OrderStatus: string
{
    case Accepted = 'accepted';
    case PendingSettlement = 'pending_settlement';
    case Settled = 'settled';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
