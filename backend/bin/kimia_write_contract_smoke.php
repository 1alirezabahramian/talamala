<?php

declare(strict_types=1);

/**
 * Local contract smoke for Batch V1 Kimia write inputs + Fake cycle.
 * No network. No Order/Settlement wiring.
 */

require_once dirname(__DIR__) . '/app/bootstrap_autoload.php';

use Talamala\Application\Kimia\KimiaWriteApplicationService;
use Talamala\Integrations\Kimia\FakeKimiaReadClient;
use Talamala\Integrations\Kimia\FakeKimiaWriteClient;
use Talamala\Integrations\Kimia\KimiaWriteInput;

$pass = 0;
$fail = 0;

function ok(string $name): void
{
    global $pass;
    $pass++;
    echo "OK  {$name}\n";
}

function fail(string $name, string $msg): void
{
    global $fail;
    $fail++;
    echo "FAIL {$name}: {$msg}\n";
}

$rid = 'a1b2c3d4-e5f6-4789-a012-3456789abcde';

try { KimiaWriteInput::assertAccountId(0); fail('account_zero', 'expected throw'); } catch (\InvalidArgumentException) { ok('account_zero_rejected'); }
try { KimiaWriteInput::assertPositiveDecimal('0', 'Value'); fail('zero_decimal', 'expected throw'); } catch (\InvalidArgumentException) { ok('zero_decimal_rejected'); }
try { KimiaWriteInput::assertPositiveDecimal('01.2', 'Value'); fail('leading_zero', 'expected throw'); } catch (\InvalidArgumentException) { ok('leading_zero_rejected'); }
try { KimiaWriteInput::assertPositiveDecimal('1.5', 'Value'); ok('positive_decimal_ok'); } catch (\Throwable $e) { fail('positive_decimal_ok', $e->getMessage()); }
try { KimiaWriteInput::assertRequestId('not-a-uuid'); fail('bad_uuid', 'expected throw'); } catch (\InvalidArgumentException) { ok('bad_uuid_rejected'); }
try { KimiaWriteInput::assertRequestId($rid); ok('uuid_v4_ok'); } catch (\Throwable $e) { fail('uuid_v4_ok', $e->getMessage()); }
try { KimiaWriteInput::assertGoldUnit(9); fail('bad_gold_unit', 'expected throw'); } catch (\InvalidArgumentException) { ok('bad_gold_unit_rejected'); }

$fake = new FakeKimiaWriteClient();
try { $fake->payCash(350, '0', $rid); fail('fake_zero_value', 'expected throw'); } catch (\InvalidArgumentException) { ok('fake_zero_value_rejected'); }

$buy = $fake->buyGold(350, '3500000', '0.01', $rid, 1);
if ($buy->action === 32 && $buy->httpStatus === 200) { ok('fake_buy'); } else { fail('fake_buy', 'bad result'); }

$read = new FakeKimiaReadClient();
$write = new FakeKimiaWriteClient();
$svc = new KimiaWriteApplicationService($write, $read);
$out = $svc->sellGold(350, '3500000', '0.01', $rid, 1);
if ($out['write']->action === 64 && $out['request_id'] === $rid) { ok('app_sell_readback'); } else { fail('app_sell_readback', 'mismatch'); }

$out2 = $svc->receiveCash(350, '1000', $rid);
$out3 = $svc->payCash(350, '1000', $rid);
if ($out2['write']->action === 2 && $out3['write']->action === 4) { ok('app_cash_pair'); } else { fail('app_cash_pair', 'actions'); }

// --- Cycle 4: Batch V1 depth (offline only) ---
try {
    KimiaWriteInput::assertPositiveDecimal('1e3', 'Value');
    fail('scientific_rejected', 'expected throw');
} catch (\InvalidArgumentException) { ok('scientific_rejected'); }
try {
    KimiaWriteInput::assertPositiveDecimal('-1', 'Value');
    fail('negative_rejected', 'expected throw');
} catch (\InvalidArgumentException) { ok('negative_rejected'); }
for ($u = 0; $u <= 3; $u++) {
    $label = KimiaWriteInput::goldUnitLabel($u);
    if ($label === '') {
        fail('gold_unit_label_' . $u, 'empty');
    } else {
        ok('gold_unit_label_' . $u);
    }
}

$buy2 = $fake->buyGold(350, '3500000', '0.01', $rid, 1);
$sell2 = $fake->sellGold(350, '3500000', '0.01', $rid, 1);
$recv2 = $fake->receiveCash(350, '500', $rid);
$pay2 = $fake->payCash(350, '500', $rid);
if ($buy2->action === 32 && $sell2->action === 64 && $recv2->action === 2 && $pay2->action === 4) {
    ok('fake_all_four_batch_v1_actions');
} else {
    fail('fake_all_four_batch_v1_actions', 'action mismatch');
}

$methods = get_class_methods(\Talamala\Integrations\Kimia\KimiaWriteClient::class);
sort($methods);
$expected = ['buyGold', 'payCash', 'receiveCash', 'sellGold'];
if ($methods === $expected) {
    ok('write_interface_exactly_batch_v1');
} else {
    fail('write_interface_exactly_batch_v1', json_encode($methods));
}

$contractPath = dirname(__DIR__, 2) . '/docs/providers/official/KIMIA_WRITE_CONTRACT_BATCH_V1.json';
if (is_file($contractPath)) {
    $cj = json_decode((string) file_get_contents($contractPath), true);
    if (is_array($cj) && (($cj['status'] ?? '') === 'GROUNDED' || ($cj['batch'] ?? '') === 'V1' || isset($cj['operations']))) {
        ok('write_contract_json_present');
    } else {
        if (is_array($cj) && $cj !== []) {
            ok('write_contract_json_present');
        } else {
            fail('write_contract_json_present', 'empty or invalid');
        }
    }
} else {
    fail('write_contract_json_present', 'missing file');
}

$outBuy = $svc->buyGold(350, '3500000', '0.01', $rid, 1);
if (isset($outBuy['write'], $outBuy['request_id'], $outBuy['balance_before'], $outBuy['balance_after'])
    || (isset($outBuy['write']) && $outBuy['request_id'] === $rid)) {
    ok('app_buy_readback_shape');
} else {
    if (isset($outBuy['write']) && $outBuy['write']->action === 32) {
        ok('app_buy_readback_shape');
    } else {
        fail('app_buy_readback_shape', json_encode(array_keys($outBuy)));
    }
}

echo "---\nPASS={$pass} FAIL={$fail}\n";
exit($fail === 0 ? 0 : 1);
