<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

final class PriceProviderUnavailableException extends \RuntimeException
{
    public static function blockedByGroundTruth(): self
    {
        return new self('Price provider BLOCKED BY GROUND TRUTH — no coefficients or feed contract');
    }
}
