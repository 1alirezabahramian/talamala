<?php

declare(strict_types=1);

/**
 * Kimia controlled verification CLI.
 *
 * Modes:
 *   preflight              Read-only P0–P6 (default)
 *   catalog                Same as preflight + print catalog summary
 *   mutate <op>            ONE gated write attempt (requires all enable flags)
 *
 * mutate ops: buy | sell | receive | pay | create
 * Payload/path MUST come from env files prepared from live Swagger — no invented Action codes.
 *
 *   KIMIA_MUTATE_PATH=/api/...
 *   KIMIA_MUTATE_BODY_FILE=/path/to/body.json
 *   KIMIA_MUTATE_ACCOUNT_ID=350   # required for buy/sell/receive/pay; omit for create
 *
 * Usage:
 *   php backend/bin/kimia_verify_runner.php preflight
 *   php backend/bin/kimia_verify_runner.php mutate buy
 *
 * Never prints passwords. Evidence → var/kimia-verify/
 */

require_once dirname(__DIR__) . '/app/bootstrap_autoload.php';

use Talamala\Integrations\Kimia\Verify\KimiaVerifyConfig;
use Talamala\Integrations\Kimia\Verify\KimiaVerifyEvidence;
use Talamala\Integrations\Kimia\Verify\KimiaVerifyHttp;
use Talamala\Integrations\Kimia\Verify\KimiaVerifyRunner;
use Talamala\Integrations\Kimia\Verify\KimiaWriteGate;

$root = dirname(__DIR__, 2);
$mode = $argv[1] ?? 'preflight';
$op = $argv[2] ?? '';

$config = KimiaVerifyConfig::fromEnv($root);
$evidence = new KimiaVerifyEvidence($config->evidenceDir);
$http = new KimiaVerifyHttp($config);
$gate = new KimiaWriteGate($config, $evidence);
$runner = new KimiaVerifyRunner($config, $evidence, $http, $gate);

$baseline = (int) (getenv('KIMIA_BASELINE_ACCOUNT_ID') ?: '350');

if ($mode === 'preflight' || $mode === 'catalog') {
    $result = $runner->runPreflight($baseline);
    if (!$result['ok']) {
        fwrite(STDERR, "PREFLIGHT_FAIL\n");
        echo json_encode(['ok' => false, 'steps' => $result['steps']], JSON_UNESCAPED_UNICODE) . "\n";
        exit(1);
    }
    echo "PREFLIGHT_OK\n";
    echo "swagger_sha256=" . ($result['swagger_sha256'] ?? '') . "\n";
    echo "swagger_version=" . ($result['swagger_version'] ?? '') . "\n";
    echo "write_attempted=false\n";
    echo "evidence_dir=" . $config->evidenceDir . "\n";
    if ($mode === 'catalog') {
        $cat = $runner->lastCatalog();
        echo "paths=" . count($cat['paths'] ?? []) . " schemas=" . count($cat['schemas'] ?? []) . "\n";
        echo "action_enums_keys=" . implode(',', array_keys($cat['action_enums'] ?? [])) . "\n";
        echo "NOTE: historical 32/64/2/4 are reference only — map from live enums + evidence\n";
    }
    exit(0);
}

if ($mode === 'mutate') {
    $allowed = ['buy', 'sell', 'receive', 'pay', 'create'];
    if (!in_array($op, $allowed, true)) {
        fwrite(STDERR, "Usage: mutate <buy|sell|receive|pay|create>\n");
        exit(2);
    }

    // Always fresh preflight before any mutate
    $pf = $runner->runPreflight($baseline);
    if (!$pf['ok']) {
        fwrite(STDERR, "STOP: preflight failed before mutate\n");
        exit(1);
    }

    $path = (string) (getenv('KIMIA_MUTATE_PATH') ?: '');
    $bodyFile = (string) (getenv('KIMIA_MUTATE_BODY_FILE') ?: '');
    $accountId = (int) (getenv('KIMIA_MUTATE_ACCOUNT_ID') ?: '0');
    if ($path === '' || $bodyFile === '' || ($op !== 'create' && $accountId <= 0)) {
        fwrite(STDERR, "STOP: set KIMIA_MUTATE_PATH and KIMIA_MUTATE_BODY_FILE from live Swagger; KIMIA_MUTATE_ACCOUNT_ID is required for account-targeted ops\n");
        fwrite(STDERR, "Runner does not invent Action codes or paths.\n");
        exit(2);
    }
    if (!is_file($bodyFile)) {
        fwrite(STDERR, "STOP: body file not found\n");
        exit(2);
    }
    $decoded = json_decode((string) file_get_contents($bodyFile), true);
    if (!is_array($decoded)) {
        fwrite(STDERR, "STOP: body file must be JSON object\n");
        exit(2);
    }

    $out = $runner->mutateOnce($op, $accountId, $path, $decoded);
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    if (($out['outcome'] ?? '') === 'outcome_unknown' || !empty($out['stop'])) {
        fwrite(STDERR, "STOP: outcome_unknown or stop — no further mutate in this session without Owner review\n");
        exit(3);
    }
    if (!($out['ok'] ?? false)) {
        exit(1);
    }
    exit(0);
}

fwrite(STDERR, "Unknown mode: {$mode}\n");
fwrite(STDERR, "Modes: preflight | catalog | mutate <op>\n");
exit(2);
