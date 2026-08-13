<?php

declare(strict_types=1);

/**
 * Aggregate local checks (no Composer required).
 * php backend/bin/check.php
 */

$root = dirname(__DIR__);
passthru('php ' . escapeshellarg($root . '/bin/smoke.php'), $a);
passthru('php ' . escapeshellarg($root . '/bin/http_smoke.php'), $b);
// Structured logger self-check
require_once $root . '/app/bootstrap_autoload.php';
use Talamala\Infrastructure\Logging\StructuredLogger;
$log = new StructuredLogger();
$log->info('check.ok', ['password' => 'secret', 'tenant_id' => 't1']);
$last = $log->records[0] ?? [];
$redacted = ($last['context']['password'] ?? null) === '[redacted]';
echo $redacted ? "OK  logger_redacts_secrets\n" : "FAIL logger_redacts_secrets\n";
$exit = ($a !== 0 || $b !== 0 || !$redacted) ? 1 : 0;
echo $exit === 0 ? "\nALL CHECKS PASSED\n" : "\nCHECKS FAILED\n";
exit($exit);
