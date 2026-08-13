<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

enum QuoteSide: string
{
    case Buy = 'buy';
    case Sell = 'sell';
}
