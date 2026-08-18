<?php

declare(strict_types=1);

// READ-ONLY local evidence extractor.
// Reads the transaction snapshot already written by KimiaVerifyRunner preflight.
// Performs no HTTP requests and no mutations.

$path = __DIR__ . '/../../var/kimia-verify/account_350_transactions_before.json';
if (!is_file($path)) {
    fwrite(STDERR, "PREFLIGHT_TX350_ERROR=snapshot_missing\n");
    exit(2);
}

$raw = (string) file_get_contents($path);
$data = json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "PREFLIGHT_TX350_ERROR=invalid_json\n");
    exit(3);
}

$wanted = [
    'AccountId', 'VoucherId', 'RecordId', 'Date', 'Action', 'ActionName',
    'ProductId', 'ProductName', 'Weight', 'Fineness', 'UnitPrice', 'GoldPrice',
    'GoldUnit', 'GoldUnitName', 'CurrencyId', 'CurrencySymbol',
    'Quantity', 'Weight750', 'SumMoney', 'Value', 'Amount', 'Money',
    'Description', 'Comment', 'RequestId',
];

$records = [];
$walk = function (mixed $node) use (&$walk, &$records): void {
    if (!is_array($node)) {
        return;
    }

    $isAssoc = array_keys($node) !== range(0, count($node) - 1);
    if ($isAssoc) {
        $keys = array_map('strtolower', array_map('strval', array_keys($node)));
        $markers = ['action', 'recordid', 'voucherid', 'actionname'];
        if (array_intersect($markers, $keys) !== []) {
            $records[] = $node;
        }
    }

    foreach ($node as $value) {
        if (is_array($value)) {
            $walk($value);
        }
    }
};
$walk($data);

$seen = [];
$unique = [];
foreach ($records as $record) {
    $fingerprint = json_encode([
        $record['RecordId'] ?? $record['recordId'] ?? null,
        $record['VoucherId'] ?? $record['voucherId'] ?? null,
        $record['Action'] ?? $record['action'] ?? null,
        $record['Date'] ?? $record['date'] ?? null,
        $record['ProductId'] ?? $record['productId'] ?? null,
        $record['Weight'] ?? $record['weight'] ?? null,
        $record['SumMoney'] ?? $record['sumMoney'] ?? null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (isset($seen[$fingerprint])) {
        continue;
    }
    $seen[$fingerprint] = true;
    $unique[] = $record;
}

printf("PREFLIGHT_TX350_EXTRACT_OK=true\n");
printf("PREFLIGHT_TX350_RAW_SHA256=%s\n", hash('sha256', $raw));
printf("PREFLIGHT_TX350_RECORD_COUNT=%d\n", count($unique));

$limit = min(20, count($unique));
for ($i = 0; $i < $limit; $i++) {
    $record = $unique[$i];
    $lowerMap = [];
    foreach ($record as $key => $value) {
        $lowerMap[strtolower((string) $key)] = [(string) $key, $value];
    }

    $out = [];
    foreach ($wanted as $wantedKey) {
        $lk = strtolower($wantedKey);
        if (isset($lowerMap[$lk])) {
            [$actualKey, $value] = $lowerMap[$lk];
            if (is_scalar($value) || $value === null) {
                $out[$actualKey] = $value;
            }
        }
    }

    printf(
        "PREFLIGHT_TX350_ROW=%s\n",
        json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)
    );
}

exit(0);
