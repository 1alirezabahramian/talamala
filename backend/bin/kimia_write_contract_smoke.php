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

echo "---\nPASS={$pass} FAIL={$fail}\n";
exit($fail === 0 ? 0 : 1);
