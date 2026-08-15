<?php

declare(strict_types=1);

/**
 * Purge expired sessions / idempotency / rate_limits.
 * php backend/bin/maintenance_smoke.php
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Domain\Idempotency\IdempotencyKey;
use Talamala\Domain\Session\SessionRecord;
use Talamala\Http\Kernel;
use Talamala\Infrastructure\Persistence\Sqlite\SqliteMaintenance;

$path = sys_get_temp_dir() . '/talamala_maint_smoke.sqlite';
@unlink($path);
putenv('TALAMALA_DB_PATH=' . $path);

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

$k = new Kernel();
$tenantId = '00000000-0000-0000-0000-000000000001';
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

// Expired session
$k->sessions->put(new SessionRecord(
    token: 'expired-token-1',
    tenantId: $tenantId,
    subjectType: 'customer',
    subjectId: 'c-1',
    expiresAt: $now->modify('-120 seconds'),
));
// Live session
$k->sessions->put(new SessionRecord(
    token: 'live-token-1',
    tenantId: $tenantId,
    subjectType: 'customer',
    subjectId: 'c-1',
    expiresAt: $now->modify('+3600 seconds'),
));
// Expired idempotency
$k->idempotency->store(
    new IdempotencyKey($tenantId, 'old-key', 'order.accept'),
    ['order_id' => 'x'],
    $now->modify('-60 seconds'),
);
// Live idempotency
$k->idempotency->store(
    new IdempotencyKey($tenantId, 'live-key', 'order.accept'),
    ['order_id' => 'y'],
    $now->modify('+3600 seconds'),
);

$maint = new SqliteMaintenance(
    Talamala\Infrastructure\Persistence\Sqlite\SqliteConnection::fromEnv()
);
$purged = $maint->purgeExpired($now);
$check('purge_sessions_gt0', ($purged['sessions'] ?? 0) >= 1, $purged);
$check('purge_idem_gt0', ($purged['idempotency'] ?? 0) >= 1, $purged);
$check('live_session_kept', $k->sessions->get('live-token-1') !== null, null);
$check('expired_session_gone', $k->sessions->get('expired-token-1') === null, null);
$check('live_idem_kept', $k->idempotency->find(new IdempotencyKey($tenantId, 'live-key', 'order.accept')) !== null, null);
$check('expired_idem_gone', $k->idempotency->find(new IdempotencyKey($tenantId, 'old-key', 'order.accept')) === null, null);

// readyz includes purged key
$r = $k->handle('GET', '/readyz', ['host' => 'demo.local'], null);
$check(
    'readyz_has_purged_ops',
    ($r['status'] ?? 0) === 200 && isset($r['body']['ops']['purged']['sessions']),
    $r['body']['ops'] ?? null
);

@unlink($path);
putenv('TALAMALA_DB_PATH');
echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
