<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap_autoload.php';

use Talamala\Domain\Order\SettlementContract;
use Talamala\Domain\Order\SettlementWireGuard;
use Talamala\Domain\Payment\PaymentContract;

$pass = 0;
$fail = 0;
$root = dirname(__DIR__, 2);
$ok = static function (string $name, bool $condition, mixed $detail = null) use (&$pass, &$fail): void {
    if ($condition) { echo "OK  {$name}\n"; ++$pass; return; }
    echo "FAIL {$name}: " . json_encode($detail, JSON_UNESCAPED_UNICODE) . "\n"; ++$fail;
};
$throws = static function (string $name, callable $fn) use ($ok): void {
    try { $fn(); $ok($name, false, 'expected throw'); } catch (\RuntimeException) { $ok($name, true); }
};

$s = SettlementContract::fromJsonFile($root . '/docs/providers/official/SETTLEMENT_CONTRACT.json');
$ok('settlement_not_grounded', $s->status === 'NOT_GROUNDED', $s->status);
$ok('settlement_flags_false', !$s->liveSettlementAuthorized && !$s->orderToKimiaWireAllowed);
$ok('settlement_unknowns_visible', $s->remainingUnknowns !== []);
$ok('settlement_public_blocked', $s->publicSettlementStatus() === 'blocked_by_ground_truth');
$throws('settlement_archive_wire_blocked', fn () => $s->assertWireAllowed());

$g = SettlementWireGuard::fromDefaultArchive($root);
$ok('settlement_guard_api_field', $g->apiSettlementField() === 'blocked_by_ground_truth');
$throws('settlement_guard_hard_stop', fn () => $g->refuseOrderKimiaWire('cycle6-test'));

$settlementUnknown = new SettlementContract(
    'GROUNDED', true, true, ['still unknown'],
    ['hold'=>'h','freeze'=>'f','release'=>'r','credit_trading'=>'c','reconciliation'=>'rec'],
    ['on_accept_order'=>'none','on_settle'=>'x','on_fail'=>'y','balance_model'=>'z'],
    'owner-policy', ['evidence-1'], []
);
$throws('settlement_grounded_with_unknown_rejected', fn () => $settlementUnknown->assertWireAllowed());

$settlementIncomplete = new SettlementContract(
    'GROUNDED', true, true, [],
    ['hold'=>'h'],
    ['on_accept_order'=>'none'],
    'owner-policy', ['evidence-1'], []
);
$throws('settlement_incomplete_semantics_rejected', fn () => $settlementIncomplete->assertWireAllowed());

$settlementNoEvidence = new SettlementContract(
    'GROUNDED', true, true, [],
    ['hold'=>'h','freeze'=>'f','release'=>'r','credit_trading'=>'c','reconciliation'=>'rec'],
    ['on_accept_order'=>'none','on_settle'=>'x','on_fail'=>'y','balance_model'=>'z'],
    null, [], []
);
$throws('settlement_missing_evidence_rejected', fn () => $settlementNoEvidence->assertWireAllowed());

$settlementComplete = new SettlementContract(
    'GROUNDED', true, true, [],
    ['hold'=>'h','freeze'=>'f','release'=>'r','credit_trading'=>'c','reconciliation'=>'rec'],
    ['on_accept_order'=>'none','on_settle'=>'x','on_fail'=>'y','balance_model'=>'z'],
    'owner-policy', ['evidence-1'], []
);
try { $settlementComplete->assertWireAllowed(); $ok('settlement_complete_fixture_contract_accepts', true); }
catch (\Throwable $e) { $ok('settlement_complete_fixture_contract_accepts', false, $e->getMessage()); }

$p = PaymentContract::fromJsonFile($root . '/docs/providers/official/PAYMENT_CONTRACT.json');
$ok('payment_not_grounded', $p->status === 'NOT_GROUNDED', $p->status);
$ok('payment_live_false', !$p->livePaymentAuthorized);
$ok('payment_unknowns_visible', $p->remainingUnknowns !== []);
$throws('payment_archive_capture_blocked', fn () => $p->assertCaptureAllowed());

$gatewayComplete = [
    'name'=>'fixture','merchant_contract_path_or_url'=>'fixture-doc','sandbox_base_url'=>'https://sandbox.invalid',
    'production_base_url'=>'https://production.invalid','callback_model'=>'fixture-callback',
    'signature_verification'=>'fixture-signature','refund_model'=>'fixture-refund','reverse_model'=>'fixture-reverse',
];
$pUnknown = new PaymentContract('GROUNDED', true, ['still unknown'], $gatewayComplete, 'owner-policy', ['evidence-1'], []);
$throws('payment_grounded_with_unknown_rejected', fn () => $pUnknown->assertCaptureAllowed());
$pIncomplete = new PaymentContract('GROUNDED', true, [], ['name'=>'fixture'], 'owner-policy', ['evidence-1'], []);
$throws('payment_incomplete_gateway_rejected', fn () => $pIncomplete->assertCaptureAllowed());
$pNoEvidence = new PaymentContract('GROUNDED', true, [], $gatewayComplete, null, [], []);
$throws('payment_missing_evidence_rejected', fn () => $pNoEvidence->assertCaptureAllowed());
$pComplete = new PaymentContract('GROUNDED', true, [], $gatewayComplete, 'owner-policy', ['evidence-1'], []);
try { $pComplete->assertCaptureAllowed(); $ok('payment_complete_fixture_contract_accepts', true); }
catch (\Throwable $e) { $ok('payment_complete_fixture_contract_accepts', false, $e->getMessage()); }

foreach (['SETTLEMENT_CONTRACT.json','SETTLEMENT_POLICY_OWNER_TEMPLATE.md','PAYMENT_CONTRACT.json','PAYMENT_POLICY_OWNER_TEMPLATE.md','SMS_JIBIT_CONTRACT.json'] as $file) {
    $ok('artifact_' . $file, is_file($root . '/docs/providers/official/' . $file));
}

$sj = json_decode((string) file_get_contents($root . '/docs/providers/official/SMS_JIBIT_CONTRACT.json'), true);
$ok('sms_jibit_not_grounded', is_array($sj) && ($sj['status'] ?? '') === 'NOT_GROUNDED');
$ok('sms_jibit_live_flags_false', is_array($sj) && empty($sj['live_sms_authorized']) && empty($sj['live_jibit_authorized']));
$ok('sms_jibit_unknowns_visible', is_array($sj) && !empty($sj['remaining_unknowns']));

echo "---\nPASS={$pass} FAIL={$fail}\n";
exit($fail === 0 ? 0 : 1);
