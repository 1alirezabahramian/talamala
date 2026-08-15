<?php

declare(strict_types=1);

/**
 * StructuredLogger redact + optional stream path.
 * php backend/bin/logger_smoke.php
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Infrastructure\Logging\StructuredLogger;

$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok, mixed $d = null) use (&$pass, &$fail): void {
    if ($ok) {
        echo "OK  $name\n";
        $pass++;
    } else {
        echo "FAIL $name " . json_encode($d, JSON_UNESCAPED_UNICODE) . "\n";
        $fail++;
    }
};

$log = new StructuredLogger();
$log->info('test.event', [
    'password' => 'secret',
    'access_token' => 'abc',
    'national_code' => '001',
    'tenant_id' => 't1',
    'safe' => 'ok',
]);
$ctx = $log->records[0]['context'] ?? [];
$check('redact_password', ($ctx['password'] ?? '') === '[redacted]', $ctx);
$check('redact_access_token', ($ctx['access_token'] ?? '') === '[redacted]', $ctx);
$check('redact_national_code', ($ctx['national_code'] ?? '') === '[redacted]', $ctx);
$check('keep_safe_fields', ($ctx['safe'] ?? '') === 'ok' && ($ctx['tenant_id'] ?? '') === 't1', $ctx);

$path = sys_get_temp_dir() . '/talamala_logger_smoke.log';
@unlink($path);
putenv('TALAMALA_LOG_PATH=' . $path);
$stream = StructuredLogger::fromEnv();
$stream->warning('stream.test', ['otp' => '123456', 'action' => 'hit']);
$check('stream_file_created', is_file($path), $path);
$raw = is_file($path) ? file_get_contents($path) : '';
$check('stream_json_line', str_contains((string) $raw, '"event":"stream.test"'), $raw);
$check('stream_redacts_otp', str_contains((string) $raw, '[redacted]') && !str_contains((string) $raw, '123456'), $raw);
@unlink($path);
putenv('TALAMALA_LOG_PATH');

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
