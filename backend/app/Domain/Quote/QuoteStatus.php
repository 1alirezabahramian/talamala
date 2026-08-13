<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

enum QuoteStatus: string
{
    case Open = 'open';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
