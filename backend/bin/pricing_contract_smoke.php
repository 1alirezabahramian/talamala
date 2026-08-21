<?php

declare(strict_types=1);

/**
 * Offline GT-004 pricing contract smoke.
 * Owner-ratified business-policy subset is verified here.
 * No network; provider GT remains incomplete and Live Pricing must stay blocked.
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

if ($c->status === 'PARTIALLY_GROUNDED') ok('pricing_status_partially_grounded'); else bad('pricing_status_partially_grounded', 'policy subset should be PARTIALLY_GROUNDED');
if ($c->livePricingAuthorized === false) ok('live_pricing_authorized_false'); else bad('live_pricing_authorized_false', 'live default must remain false');
if ($c->remainingUnknowns !== []) ok('provider_unknowns_still_listed'); else bad('provider_unknowns_still_listed', 'provider unknowns must remain');

try { $c->assertLivePricingAllowed(); bad('assert_live_still_blocked', 'expected throw'); }
catch (PriceProviderUnavailableException) { ok('assert_live_still_blocked'); }

foreach (['PRICING_POLICY_OWNER_TEMPLATE.md', 'KIMIA_CREATE_CONTROLLED_RUNBOOK.md', 'PRICING_CONTRACT.json', 'PRICING_POLICY_OWNER_RATIFIED_20260821.md'] as $f) {
    if (is_file($root . '/docs/providers/official/' . $f)) ok('artifact_' . $f);
    else bad('artifact_' . $f, 'missing');
}

// Ratified business-policy evidence — these are Owner facts now, not fixture defaults.
$coeff = $c->raw['coefficients'] ?? [];
if (($coeff['x'] ?? null) === '1' && ($coeff['y'] ?? null) === '0' && ($coeff['z'] ?? null) === '0') ok('ratified_coefficients_xyz'); else bad('ratified_coefficients_xyz', 'unexpected coefficients');
if (($coeff['application_order'] ?? null) === 'adjusted_unit = (reference_unit * x) + y + z') ok('ratified_coefficient_order'); else bad('ratified_coefficient_order', 'unexpected application order');

$rounding = $c->raw['rounding'] ?? [];
if (($rounding['mode'] ?? null) === 'half_up' && ($rounding['scale_rial'] ?? null) === 0 && ($rounding['scale_total_rial'] ?? null) === 0 && ($rounding['scale_quantity'] ?? null) === 4) ok('ratified_rounding'); else bad('ratified_rounding', 'unexpected rounding policy');

$quote = $c->raw['quote_policy'] ?? [];
if (($quote['default_ttl_seconds'] ?? null) === 120 && ($quote['max_ttl_seconds'] ?? null) === 300) ok('ratified_quote_ttl'); else bad('ratified_quote_ttl', 'unexpected TTL');
if (($quote['freeze_on_accept'] ?? null) === true) ok('ratified_freeze_on_accept'); else bad('ratified_freeze_on_accept', 'must be true');
if (($quote['accepted_order_behavior'] ?? null) === 'preserve immutable accepted quote snapshot; do not re-price') ok('ratified_no_reprice'); else bad('ratified_no_reprice', 'unexpected accepted behavior');
if (($c->raw['proposal_status'] ?? null) === 'OWNER_RATIFIED_POLICY_SUBSET') ok('owner_ratification_recorded'); else bad('owner_ratification_recorded', 'ratification metadata missing');
if (($c->raw['blocked_scope'][0] ?? null) === 'FA-048 live price provider integration') ok('provider_scope_explicitly_blocked'); else bad('provider_scope_explicitly_blocked', 'FA-048 must stay blocked');

// Complete fictional contract is used only to test completeness mechanics.
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
    } catch (PriceProviderUnavailableException) { ok($name); }
}

try {
    (new PricingContract('GROUNDED', true, ['still unknown'], $base))->assertLivePricingAllowed();
    bad('refuse_remaining_unknowns', 'expected throw');
} catch (PriceProviderUnavailableException) { ok('refuse_remaining_unknowns'); }

try {
    (new PricingContract('GROUNDED', true, [], $base))->assertLivePricingAllowed();
    ok('allows_only_complete_fixture');
} catch (PriceProviderUnavailableException $e) { bad('allows_only_complete_fixture', $e->getMessage()); }

if (is_file($root . '/docs/providers/official/PRICING_POLICY_PROPOSED_FOR_OWNER.md')) ok('proposal_doc_preserved');
else bad('proposal_doc_preserved', 'missing');

$blocked = BlockedPriceProvider::fromDefaultArchive($root);
try {
    $blocked->getUnitPriceRial('tenant-fixture', QuoteAsset::Gold18, null);
    bad('blocked_provider_throws', 'expected throw');
} catch (PriceProviderUnavailableException) { ok('blocked_provider_throws'); }

$guard = QuoteIssuanceGuard::fromDefaultArchive($root);
if ($guard->isLivePricingOpen() === false) ok('issuance_live_closed');
else bad('issuance_live_closed', 'live pricing must remain closed');

try {
    $guard->assertSourceAllowed('live-market-tick');
    bad('refuse_live_looking_ref', 'expected complete live gate to throw');
} catch (PriceProviderUnavailableException) { ok('refuse_live_looking_ref'); }

try {
    $guard->assertSourceAllowed('dev-manual-fixture');
    ok('allow_explicit_nonlive_ref');
} catch (PriceProviderUnavailableException $e) { bad('allow_explicit_nonlive_ref', $e->getMessage()); }

echo "---\nPASS={$pass} FAIL={$fail}\n";
echo "NOTE: Owner policy subset is ratified; live provider scope remains blocked and no network was used.\n";
exit($fail === 0 ? 0 : 1);
