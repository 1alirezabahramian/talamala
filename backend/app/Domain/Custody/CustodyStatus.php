<?php

declare(strict_types=1);

namespace Talamala\Domain\Custody;

enum CustodyStatus: string
{
    case Held = 'held';
    case ReadyForPickup = 'ready_for_pickup';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
