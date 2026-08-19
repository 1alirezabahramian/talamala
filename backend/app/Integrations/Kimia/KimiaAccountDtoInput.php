<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * AccountDto field checks from grounded live Swagger (GT-002 PARTIAL).
 * Type create-allowlist from Create operation description only: بنکداری/تکفروشی/امانات → 1, 3, 10.
 * Does not invent validation/duplicate HTTP semantics beyond schema maxLength/types.
 */
final class KimiaAccountDtoInput
{
    /** Create operation description — not full Type enum on DTO. */
    public const CREATE_ALLOWED_TYPES = [1, 3, 10];

    private const MAX = [
        'Address' => 255,
        'Comment' => 500,
        'EconomicCode' => 20,
        'Mobile' => 255,
        'Name' => 255,
        'NationalCode' => 20,
        'PostalCode' => 10,
        'ShopName' => 255,
        'Tel' => 255,
    ];

    /**
     * @param array<string, mixed> $payload
     */
    public static function assertValues(array $payload): void
    {
        foreach ($payload as $key => $value) {
            if ($value === null) {
                continue;
            }
            switch ($key) {
                case 'Name':
                case 'Mobile':
                case 'ShopName':
                case 'Tel':
                case 'Address':
                case 'Comment':
                case 'NationalCode':
                case 'EconomicCode':
                case 'PostalCode':
                    if (!is_string($value)) {
                        throw new \InvalidArgumentException($key . ' must be string or null');
                    }
                    $max = self::MAX[$key];
                    if (strlen($value) > $max) {
                        throw new \InvalidArgumentException($key . ' exceeds maxLength ' . $max);
                    }
                    break;
                case 'Type':
                    if (!is_int($value)) {
                        throw new \InvalidArgumentException('Type must be int32 or null');
                    }
                    self::assertInt32('Type', $value);
                    $t = $value;
                    if (!in_array($t, self::CREATE_ALLOWED_TYPES, true)) {
                        throw new \InvalidArgumentException(
                            'Type for Create must be one of 1 (بنکداری), 3 (تکفروشی), 10 (امانات) per operation description'
                        );
                    }
                    break;
                case 'IsVisible':
                    if (!is_bool($value)) {
                        throw new \InvalidArgumentException('IsVisible must be boolean or null');
                    }
                    break;
                case 'AccountCode':
                case 'AccountId':
                    if (!is_int($value)) {
                        throw new \InvalidArgumentException($key . ' must be int32 or null');
                    }
                    self::assertInt32($key, $value);
                    break;
                case 'DateBirthday':
                    if (!is_string($value)) {
                        throw new \InvalidArgumentException('DateBirthday must be string (date-time) or null');
                    }
                    break;
                default:
                    // unknown keys rejected earlier by assertPayloadKeys
                    break;
            }
        }
    }

    private static function assertInt32(string $key, int $value): void
    {
        if ($value < -2147483648 || $value > 2147483647) {
            throw new \InvalidArgumentException($key . ' must fit int32');
        }
    }
}
