<?php

declare(strict_types=1);

/**
 * Standalone decimal-string invariants for money/weight.
 * php backend/bin/decimal_invariant_smoke.php
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Domain\Custody\CustodyItem;
use Talamala\Domain\Custody\CustodyStatus;
use Talamala\Domain\Order\Order;
use Talamala\Domain\Order\OrderStatus;
use Talamala\Domain\Quote\Quote;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteSide;
use Talamala\Domain\Quote\QuoteStatus;
use Talamala\Domain\Shared\DecimalString;

$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok) use (&$pass, &$fail): void {
    if ($ok) {
        echo "OK  $name\n";
        $pass++;
    } else {
        echo "FAIL $name\n";
        $fail++;
    }
};

$check('canonical_plain', DecimalString::isCanonical('1.500'));
$check('canonical_zero', DecimalString::isCanonical('0'));
$check('canonical_negative', DecimalString::isCanonical('-2.25'));
$check('reject_empty', !DecimalString::isCanonical(''));
$check('reject_scientific', !DecimalString::isCanonical('1e3'));
$check('reject_multi_dot', !DecimalString::isCanonical('1.2.3'));
$check('reject_alpha', !DecimalString::isCanonical('12a'));

$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
try {
    new Quote('q1', 't1', 'c1', QuoteSide::Buy, QuoteAsset::Gold18, '1.000', '100', '100', $now, $now->modify('+1 minute'), QuoteStatus::Open);
    $check('quote_accepts_canonical', true);
} catch (Throwable) {
    $check('quote_accepts_canonical', false);
}

try {
    new Quote('q2', 't1', 'c1', QuoteSide::Buy, QuoteAsset::Gold18, '1e2', '100', '100', $now, $now->modify('+1 minute'), QuoteStatus::Open);
    $check('quote_rejects_scientific', false);
} catch (InvalidArgumentException) {
    $check('quote_rejects_scientific', true);
}

try {
    new Order('o1', 't1', 'c1', 'q1', QuoteSide::Buy, QuoteAsset::Gold18, '1.000', '100', '100', OrderStatus::Accepted, $now);
    $check('order_accepts_canonical', true);
} catch (Throwable) {
    $check('order_accepts_canonical', false);
}

try {
    new Order('o2', 't1', 'c1', 'q1', QuoteSide::Buy, QuoteAsset::Gold18, '1e2', '100', '100', OrderStatus::Accepted, $now);
    $check('order_rejects_scientific', false);
} catch (InvalidArgumentException) {
    $check('order_rejects_scientific', true);
}

try {
    new CustodyItem('cu1', 't1', 'c1', 'test', '8.100', '900', CustodyStatus::Held, $now);
    $check('custody_accepts_canonical', true);
} catch (Throwable) {
    $check('custody_accepts_canonical', false);
}

try {
    new CustodyItem('cu2', 't1', 'c1', 'test', '1e3', '900', CustodyStatus::Held, $now);
    $check('custody_rejects_scientific', false);
} catch (InvalidArgumentException) {
    $check('custody_rejects_scientific', true);
}

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
