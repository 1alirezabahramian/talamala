<?php

declare(strict_types=1);

namespace Talamala\Domain\Shared;

/**
 * Canonical non-float decimal for money/weight.
 * Policy: money/weight travel as decimal strings only — never PHP float.
 */
final class DecimalString
{
    /**
     * Accepts optional leading sign, digits, optional single fractional part.
     * Rejects empty, scientific notation, thousands separators, NaN/Inf text.
     */
    public static function assertCanonical(string $value, string $field = 'value'): string
    {
        $v = trim($value);
        if ($v === '') {
            throw new \InvalidArgumentException($field . ' must be a non-empty decimal string');
        }
        if ($v !== $value) {
            throw new \InvalidArgumentException($field . ' must not contain surrounding whitespace');
        }
        if (preg_match('/[eE]/', $v) === 1) {
            throw new \InvalidArgumentException($field . ' must not use scientific notation');
        }
        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $v) !== 1) {
            throw new \InvalidArgumentException($field . ' must be a canonical decimal string');
        }
        // Normalize "-0" / "00.50" is allowed as-is for auditability; callers may pre-normalize.
        return $v;
    }

    public static function isCanonical(string $value): bool
    {
        try {
            self::assertCanonical($value);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
