<?php

declare(strict_types=1);

/**
 * Offline GT-004 pricing contract smoke.
 * No network. No invented coefficients/provider/TTL/rounding. Live pricing stays blocked.
 */

require_once dirname(__DIR__) . '/app/bootstrap_autoload.php';

use Talamala\Domain\Quote\BlockedPriceProvider;
use Talamala\Domain\Quote\PriceProviderUnavailableException;
use Talamala\Domain\Quote\PricingContract;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteIssuanceGuard;

$pass = 0;
$fail = 0;
$root = dirname(__DIR__, 2);

function ok(string $n): void { global $pass; ++$pass; echo "OK  {$n}\n"; }
function bad(string $n, string $m): void { global $fail; ++$fail; echo "FAIL {$n}: {$m}\n"; }

$path = $root . '/docs/providers/official/PRICING_CONTRACT.json';
$c = PricingContract::fromJsonFile($path);

if ($c->status === 'NOT_GROUNDED') ok('pricing_status_not_grounded'); else bad('pricing_status_not_grounded', 'must remain NOT_GROUNDED');
if ($c->livePricingAuthorized === false) ok('live_pricing_authorized_false'); else bad('live_pricing_authorized_false', 'default must be false');
if ($c->remainingUnknowns !== []) ok('remaining_unknowns_listed'); else bad('remaining_unknowns_listed', 'expected unknowns');

try { $c->assertLivePricingAllowed(); bad('assert_live_blocked', 'expected throw'); }
catch (PriceProviderUnavailableException) { ok('assert_live_blocked'); }

foreach (['PRICING_POLICY_OWNER_TEMPLATE.md', 'KIMIA_CREATE_CONTROLLED_RUNBOOK.md', 'PRICING_CONTRACT.json'] as $f) {
    if (is_file($root . '/docs/providers/official/' . $f)) ok('artifact_' . $f);
    else bad('artifact_' . $f, 'missing');
}

$base = [
    'provider' => [
        'name' => 'Fixture Provider',
        'official_api_doc_url_or_path' => 'docs/providers/official/FIXTURE_ONLY.md',
        'auth_model' => 'fixture',
        'freshness_sla_seconds' => 5,
        'failover_policy' => 'reject stale; no fallback',
        'observed_at_field' => 'observed_at',
    ],
    'assets_supported' => ['fixture-gold'],
    'coefficients' => ['x' => '1', 'y' => '0', 'z' => '0'],
    'rounding' => ['order' => 'unit_price_then_total', 'mode' => 'half_up', 'scale_rial' => 0, 'scale_quantity' => 3],
    'quote_policy' => ['default_ttl_seconds' => 60, 'max_ttl_seconds' => 60, 'freeze_on_accept' => true, 'authority' => 'fixture-owner'],
];

$cases = [
    'refuse_missing_provider' => (function() use ($base) { $x=$base; unset($x['provider']); return $x; })(),
    'refuse_missing_freshness' => (function() use ($base) { $x=$base; $x['provider']['freshness_sla_seconds']=null; return $x; })(),
    'refuse_empty_assets' => (function() use ($base) { $x=$base; $x['assets_supported']=[]; return $x; })(),
    'refuse_null_coefficients' => (function() use ($base) { $x=$base; $x['coefficients']['x']=null; return $x; })(),
    'refuse_missing_rounding' => (function() use ($base) { $x=$base; $x['rounding']['mode']=null; return $x; })(),
    'refuse_null_ttl' => (function() use ($base) { $x=$base; $x['quote_policy']['default_ttl_seconds']=null; return $x; })(),
];

foreach ($cases as $name => $raw) {
    try {
        (new PricingContract('GROUNDED', true, [], $raw))->assertLivePricingAllowed();
        bad($name, 'expected throw');
    } catch (PriceProviderUnavailableException) {
        ok($name);
    }
}

try {
    (new PricingContract('GROUNDED', true, ['still unknown'], $base))->assertLivePricingAllowed();
    bad('refuse_remaining_unknowns', 'expected throw');
} catch (PriceProviderUnavailableException) {
    ok('refuse_remaining_unknowns');
}

try {
    (new PricingContract('GROUNDED', true, [], $base))->assertLivePricingAllowed();
    ok('allows_only_complete_fixture');
} catch (PriceProviderUnavailableException $e) {
    bad('allows_only_complete_fixture', $e->getMessage());
}

if (($c->raw['proposal_status'] ?? null) === 'AWAITING_OWNER_RATIFICATION') {
    ok('proposal_awaiting_owner_ratification');
} else {
    bad('proposal_awaiting_owner_ratification', 'proposal must not be ratified by code');
}

if (is_file($root . '/docs/providers/official/PRICING_POLICY_PROPOSED_FOR_OWNER.md')) {
    ok('proposal_doc_present');
} else {
    bad('proposal_doc_present', 'missing');
}

$blocked = BlockedPriceProvider::fromDefaultArchive($root);
try {
    $blocked->getUnitPriceRial('tenant-fixture', QuoteAsset::Gold18, null);
    bad('blocked_provider_throws', 'expected throw');
} catch (PriceProviderUnavailableException) {
    ok('blocked_provider_throws');
}

$guard = QuoteIssuanceGuard::fromDefaultArchive($root);
if ($guard->isLivePricingOpen() === false) ok('issuance_live_closed');
else bad('issuance_live_closed', 'live pricing must remain closed');

try {
    $guard->assertSourceAllowed('live-market-tick');
    bad('refuse_live_looking_ref', 'expected complete live gate to throw');
} catch (PriceProviderUnavailableException) {
    ok('refuse_live_looking_ref');
}

try {
    $guard->assertSourceAllowed('dev-manual-fixture');
    ok('allow_explicit_nonlive_ref');
} catch (PriceProviderUnavailableException $e) {
    bad('allow_explicit_nonlive_ref', $e->getMessage());
}

echo "---\nPASS={$pass} FAIL={$fail}\n";
echo "NOTE: fixture values above are test-only and are NOT business defaults or GT-004 evidence.\n";
exit($fail === 0 ? 0 : 1);
