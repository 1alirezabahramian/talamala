<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * Shared input guards for Batch V1 write contracts.
 * Used by HttpKimiaWriteClient and FakeKimiaWriteClient so tests mirror production rules.
 */
final class KimiaWriteInput
{
    public static function assertAccountId(int $accountId): void
    {
        if ($accountId <= 0) {
            throw new \InvalidArgumentException('AccountId must be positive');
        }
    }

    public static function assertPositiveDecimal(string $value, string $field): void
    {
        $value = trim($value);
        if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $value)) {
            throw new \InvalidArgumentException($field . ' must be a canonical decimal string');
        }
        if (preg_match('/^0(?:\.0+)?$/', $value)) {
            throw new \InvalidArgumentException($field . ' must be greater than zero');
        }
    }

    public static function assertRequestId(string $requestId): void
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $requestId)) {
            throw new \InvalidArgumentException('RequestId must be a UUID v4');
        }
    }

    public static function assertGoldUnit(int $goldUnit): void
    {
        if (!in_array($goldUnit, [0, 1, 2, 3], true)) {
            throw new \InvalidArgumentException('GoldUnit must be one of 0,1,2,3');
        }
    }

    /** GoldUnit meanings from live Swagger (SOURCE_REGISTER). */
    public static function goldUnitLabel(int $goldUnit): string
    {
        self::assertGoldUnit($goldUnit);

        return match ($goldUnit) {
            0 => 'mesghal',
            1 => 'gram',
            2 => 'ounce',
            3 => 'kilogram',
        };
    }
}
