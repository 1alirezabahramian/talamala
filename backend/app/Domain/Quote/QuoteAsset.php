<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

enum QuoteAsset: string
{
    case Gold18 = 'gold18';
    case Coin = 'coin';
    case Currency = 'currency';
}
