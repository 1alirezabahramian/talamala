<?php

declare(strict_types=1);

/**
 * Kimia Write Verification — Read-Only Preflight (P0–P6).
 *
 * Default-deny: exits non-zero if env incomplete or any step fails.
 * NEVER sends Write/mutation HTTP methods.
 * NEVER prints password. Evidence under var/kimia-verify/ (gitignored).
 *
 * Usage:
 *   export KIMIA_BASE_URL=...
 *   export KIMIA_USERNAME=...
 *   export KIMIA_PASSWORD=...
 *   php backend/bin/kimia_preflight_readonly.php
 */

$root = dirname(__DIR__, 2);
$outDir = $root . '/var/kimia-verify';
if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) {
    fwrite(STDERR, "FAIL cannot create $outDir\n");
    exit(2);
}

$base = rtrim((string) (getenv('KIMIA_BASE_URL') ?: ''), '/');
$user = (string) (getenv('KIMIA_USERNAME') ?: '');
$pass = (string) (getenv('KIMIA_PASSWORD') ?: '');
$swaggerUrl = (string) (getenv('KIMIA_SWAGGER_URL') ?: '');
if ($swaggerUrl === '' && $base !== '') {
    $swaggerUrl = $base . '/swagger/v1/swagger.json';
}

$result = [
    'ts' => gmdate('c'),
    'mode' => 'read_only_preflight',
    'write_attempted' => false,
    'steps' => [],
];

$fail = static function (string $step, string $msg) use (&$result, $outDir): void {
    $result['steps'][$step] = ['ok' => false, 'message' => $msg];
    $result['ok'] = false;
    file_put_contents($outDir . '/preflight_result.json', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fwrite(STDERR, "FAIL $step: $msg\n");
    exit(1);
};

$passStep = static function (string $step, array $extra = []) use (&$result): void {
    $result['steps'][$step] = array_merge(['ok' => true], $extra);
    echo "OK  $step\n";
};

// --- env gate ---
if ($base === '' || $user === '' || $pass === '') {
    $fail('env', 'KIMIA_BASE_URL, KIMIA_USERNAME, KIMIA_PASSWORD required (secrets not logged)');
}

$host = parse_url($base, PHP_URL_HOST) ?: 'unknown';
$result['base_host'] = $host;

/**
 * @return array{status:int,body:string,err:string}
 */
$http = static function (string $method, string $url, string $user, string $pass, bool $auth) : array {
    if (strtoupper($method) !== 'GET') {
        return ['status' => 0, 'body' => '', 'err' => 'preflight_rejects_non_get'];
    }
    $ch = curl_init($url);
    if ($ch === false) {
        return ['status' => 0, 'body' => '', 'err' => 'curl_init_failed'];
    }
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_CUSTOMREQUEST => 'GET',
    ];
    if ($auth) {
        $opts[CURLOPT_USERPWD] = $user . ':' . $pass;
        $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno !== 0) {
        return ['status' => 0, 'body' => '', 'err' => $err !== '' ? $err : 'curl_' . $errno];
    }
    return ['status' => $status, 'body' => is_string($body) ? $body : '', 'err' => ''];
};

// P0 connectivity — unauthenticated GET to swagger URL (or base)
$probeUrl = $swaggerUrl !== '' ? $swaggerUrl : $base . '/';
$r0 = $http('GET', $probeUrl, $user, $pass, false);
if ($r0['status'] === 0) {
    $fail('P0_connectivity', $r0['err'] !== '' ? $r0['err'] : 'unreachable');
}
$passStep('P0_connectivity', ['http_status' => $r0['status'], 'probe' => $probeUrl]);

// P1 live swagger
$r1 = $http('GET', $swaggerUrl, $user, $pass, false);
if ($r1['status'] === 0 || $r1['body'] === '') {
    // retry with auth in case swagger is protected
    $r1 = $http('GET', $swaggerUrl, $user, $pass, true);
}
if ($r1['status'] < 200 || $r1['status'] >= 300 || $r1['body'] === '') {
    $fail('P1_live_swagger', 'HTTP ' . $r1['status'] . ' err=' . $r1['err']);
}
$swaggerHash = hash('sha256', $r1['body']);
$swaggerJson = json_decode($r1['body'], true);
$swaggerVersion = is_array($swaggerJson) ? (string) ($swaggerJson['info']['version'] ?? 'unknown') : 'non_json';
file_put_contents($outDir . '/swagger_live.json', $r1['body']);
file_put_contents($outDir . '/preflight_meta.json', json_encode([
    'ts' => gmdate('c'),
    'base_host' => $host,
    'swagger_url' => $swaggerUrl,
    'swagger_http_status' => $r1['status'],
    'swagger_version' => $swaggerVersion,
    'swagger_sha256' => $swaggerHash,
    'archive_note' => 'docs/providers/official/KIMIA_SWAGGER_ARCHIVE_NOTE.md',
    'archive_blob_sha_cited' => 'ea3de1aa56c6f2a940eba24a6c4f57eb9fc904ed',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
$passStep('P1_live_swagger', [
    'version' => $swaggerVersion,
    'sha256' => $swaggerHash,
    'bytes' => strlen($r1['body']),
]);

// P2 archive diff (heuristic on live paths if OpenAPI)
$archiveNote = @file_get_contents($root . '/docs/providers/official/KIMIA_SWAGGER_ARCHIVE_NOTE.md') ?: '';
$writeKeywords = ['Exchange', 'TradeCash', 'TradeCurrency', 'Transfer', 'Adjustment', 'RequestId'];
$found = [];
$paths = [];
if (is_array($swaggerJson) && isset($swaggerJson['paths']) && is_array($swaggerJson['paths'])) {
    foreach ($swaggerJson['paths'] as $path => $ops) {
        $p = (string) $path;
        if (preg_match('/exchange|trade|transfer|voucher|account|cash|adjust/i', $p)) {
            $paths[$p] = array_values(array_filter(array_keys(is_array($ops) ? $ops : [], static fn ($k) => is_string($k) && preg_match('/get|post|put|patch|delete/i', $k))));
        }
    }
}
foreach ($writeKeywords as $kw) {
    $found[$kw] = str_contains($r1['body'], $kw);
}
$diffDoc = "# Swagger write-related extract (live)\n\n"
    . "- version: `{$swaggerVersion}`\n"
    . "- sha256: `{$swaggerHash}`\n"
    . "- archive note blob cited: `ea3de1aa56c6f2a940eba24a6c4f57eb9fc904ed`\n\n"
    . "## Paths matching trade/account/voucher/exchange\n\n"
    . "```json\n" . json_encode($paths, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n```\n\n"
    . "## Keyword presence in live body\n\n"
    . "```json\n" . json_encode($found, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n```\n\n"
    . "## Archive note (reference only — not sole Write authority)\n\n"
    . "See KIMIA_SWAGGER_ARCHIVE_NOTE.md. Owner must approve any schema/action delta before Write.\n";
file_put_contents($outDir . '/swagger_diff_write_related.md', $diffDoc);
$passStep('P2_archive_diff', [
    'write_related_paths' => count($paths),
    'note' => 'diff artifact written; Owner review required before Write if schema/actions diverge',
]);

// P3 auth check — GET /api/account (confirmed Read path from archive + Stage 3)
$accountUrl = $base . '/api/account';
$r3 = $http('GET', $accountUrl, $user, $pass, true);
if ($r3['status'] === 401 || $r3['status'] === 403) {
    $fail('P3_auth', 'HTTP ' . $r3['status']);
}
if ($r3['status'] < 200 || $r3['status'] >= 300) {
    $fail('P3_auth', 'HTTP ' . $r3['status'] . ' ' . $r3['err']);
}
file_put_contents($outDir . '/account_list_raw.json', $r3['body']);
$passStep('P3_auth', ['http_status' => $r3['status']]);

// P4 account 350 readable
$accounts = json_decode($r3['body'], true);
$has350 = false;
if (is_array($accounts)) {
    $flat = $accounts;
    // list or wrapped
    if (isset($accounts[0]) || $accounts === []) {
        $flat = $accounts;
    }
    foreach ($flat as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = $row['Id'] ?? $row['id'] ?? $row['AccountId'] ?? $row['accountId'] ?? null;
        if ((int) $id === 350) {
            $has350 = true;
            break;
        }
    }
}
if (!$has350) {
    // still attempt balance endpoint — some deployments list filters
    $result['steps']['P4_account_350'] = ['ok' => false, 'message' => 'id 350 not found in account list body; will still try balance'];
    echo "WARN P4_account_350: not in list; trying balance endpoint\n";
} else {
    $passStep('P4_account_350', ['found_in_list' => true]);
}

// P5 balance baseline
$balUrl = $base . '/api/voucher/balance/350';
$r5 = $http('GET', $balUrl, $user, $pass, true);
if ($r5['status'] < 200 || $r5['status'] >= 300) {
    $fail('P5_balance_baseline', 'HTTP ' . $r5['status']);
}
file_put_contents($outDir . '/account_350_balance_before.json', $r5['body']);
$passStep('P5_balance_baseline', ['http_status' => $r5['status'], 'bytes' => strlen($r5['body'])]);
if (!$has350) {
    $passStep('P4_account_350', ['found_in_list' => false, 'balance_endpoint_ok' => true]);
}

// P6 transactions baseline
$txUrl = $base . '/api/voucher/transactions/350?pageNumber=0';
$r6 = $http('GET', $txUrl, $user, $pass, true);
if ($r6['status'] < 200 || $r6['status'] >= 300) {
    $fail('P6_tx_baseline', 'HTTP ' . $r6['status']);
}
file_put_contents($outDir . '/account_350_transactions_before.json', $r6['body']);
$passStep('P6_tx_baseline', ['http_status' => $r6['status'], 'bytes' => strlen($r6['body'])]);

$result['ok'] = true;
$result['write_gate'] = [
    'KIMIA_WRITE_VERIFY_ENABLE' => getenv('KIMIA_WRITE_VERIFY_ENABLE') ?: 'unset',
    'note' => 'Write remains blocked until Owner enables after reviewing evidence',
];
file_put_contents($outDir . '/preflight_result.json', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "\nPREFLIGHT_OK evidence in var/kimia-verify/\n";
echo "swagger_sha256=$swaggerHash version=$swaggerVersion\n";
echo "Write not attempted (read_only).\n";
exit(0);
