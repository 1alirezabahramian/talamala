<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia\Verify;

/**
 * Executes exactly one already-authorized verification mutation.
 * All mutation safety remains default-deny and attempt-reserved before network send.
 */
final class KimiaMutationVerifier
{
    public function __construct(
        private readonly KimiaVerifyConfig $config,
        private readonly KimiaVerifyEvidence $evidence,
        private readonly KimiaVerifyHttp $http,
        private readonly KimiaWriteGate $gate,
        private readonly array $liveCatalog,
    ) {}

    public function mutateOnce(
        string $operation,
        int $accountId,
        string $path,
        array $payloadDecoded,
        bool $preflightOk,
    ): array {
        $gate = $this->gate->assertMayMutate($operation, $accountId, $preflightOk);
        if (!$gate['ok']) {
            $this->evidence->appendLog("WRITE_BLOCKED op={$operation} reason=" . ($gate['reason'] ?? ''));
            return ['ok' => false, 'outcome' => 'blocked', 'message' => $gate['reason'] ?? 'blocked'];
        }

        // Path must be a relative POST path present in the fresh live Swagger catalog.
        if (!str_starts_with($path, '/') || str_contains($path, '://') || str_contains($path, '?') || str_contains($path, '#')) {
            return ['ok' => false, 'outcome' => 'blocked', 'message' => 'mutation path must be an exact relative Swagger path'];
        }
        $postPaths = $this->liveCatalog['post_paths'] ?? [];
        if (!is_array($postPaths) || !in_array($path, $postPaths, true)) {
            return ['ok' => false, 'outcome' => 'blocked', 'message' => 'mutation path is not a POST path in fresh live Swagger'];
        }

        // Defense against allowlist bypass: account-targeted body must match the gated account id.
        if ($operation !== 'create') {
            $payloadAccountId = $this->extractPayloadAccountId($payloadDecoded);
            if ($payloadAccountId === null) {
                return ['ok' => false, 'outcome' => 'blocked', 'message' => 'payload has no AccountId/accountId field; cannot prove allowlisted target'];
            }
            if ($payloadAccountId !== $accountId) {
                return ['ok' => false, 'outcome' => 'blocked', 'message' => 'payload AccountId does not match gated account id'];
            }
        }

        $attemptId = bin2hex(random_bytes(12));
        $beforeLabel = $operation . '_' . $attemptId . '_before';
        $before = $operation === 'create'
            ? $this->snapshotAccountList($beforeLabel)
            : $this->readback($accountId, $beforeLabel);
        if (!$before['ok']) {
            $this->evidence->appendLog("WRITE_BLOCKED op={$operation} reason=before_readback_failed");
            return ['ok' => false, 'outcome' => 'blocked', 'message' => 'before-readback failed; no mutation attempted', 'stop' => true];
        }

        $body = json_encode($payloadDecoded, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return ['ok' => false, 'outcome' => 'invalid_payload', 'message' => 'json_encode failed'];
        }

        $prefix = 'writes/' . $this->gate->batchEvidencePrefix() . '/' . $operation . '/' . $attemptId;
        $pretty = json_encode($payloadDecoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (is_string($pretty)) {
            $this->evidence->writeRaw($prefix . '/request_raw.json', $pretty . "\n");
        }
        $this->evidence->writeJson($prefix . '/request_meta.json', [
            'ts' => gmdate('c'),
            'attempt_id' => $attemptId,
            'operation' => $operation,
            'account_id' => $accountId > 0 ? $accountId : null,
            'path' => $path,
            'payload_keys' => array_keys($payloadDecoded),
            'payload_sha256' => hash('sha256', $body),
        ]);

        try {
            // Atomic reserve BEFORE send; concurrent/pending attempts fail closed.
            $this->gate->reserveAttempt($operation, $accountId, $path, $attemptId);
        } catch (\Throwable $e) {
            $this->evidence->appendLog("WRITE_BLOCKED op={$operation} reason=reserve_failed");
            return ['ok' => false, 'outcome' => 'blocked', 'message' => 'attempt reserve failed: ' . $e->getMessage(), 'stop' => true];
        }

        $resp = $this->http->post($path, $body, true);
        $this->evidence->writeRaw($prefix . '/response_raw.txt', $resp['body']);
        $this->evidence->writeJson($prefix . '/response_meta.json', [
            'http_status' => $resp['status'],
            'body_bytes' => strlen($resp['body']),
            'timed_out' => $resp['timed_out'],
            'transport_error_present' => $resp['err'] !== '',
        ]);

        if ($resp['timed_out'] || ($resp['status'] === 0 && $resp['err'] !== '')) {
            $rb = $operation === 'create'
                ? $this->snapshotAccountList($operation . '_' . $attemptId . '_after_unknown')
                : $this->readback($accountId, $operation . '_' . $attemptId . '_after_unknown');
            $this->gate->finalizeAttempt(
                $attemptId,
                'outcome_unknown',
                ['http_status' => $resp['status'], 'err' => $resp['err'], 'timed_out' => $resp['timed_out'], 'readback_ok' => $rb['ok']],
                "{$operation}:outcome_unknown"
            );
            $this->evidence->appendLog("OUTCOME_UNKNOWN op={$operation} — BATCH_HALTED after readback");
            return [
                'ok' => false,
                'outcome' => 'outcome_unknown',
                'http_status' => $resp['status'],
                'message' => $resp['err'] ?: 'timeout_or_transport',
                'stop' => true,
                'readback_ok' => $rb['ok'],
            ];
        }

        if ($resp['status'] >= 200 && $resp['status'] < 300) {
            $rb = $operation === 'create'
                ? $this->snapshotAccountList($operation . '_' . $attemptId . '_after')
                : $this->readback($accountId, $operation . '_' . $attemptId . '_after');
            if (!$rb['ok']) {
                $this->gate->finalizeAttempt(
                    $attemptId,
                    'success_readback_failed',
                    ['http_status' => $resp['status'], 'readback_ok' => false],
                    "{$operation}:success_readback_failed"
                );
                $this->evidence->appendLog("WRITE_OK_READBACK_FAIL op={$operation} status=" . $resp['status'] . ' — BATCH_HALTED');
                return ['ok' => false, 'outcome' => 'success_readback_failed', 'http_status' => $resp['status'], 'message' => 'mutation succeeded but readback failed', 'stop' => true];
            }
            $this->gate->finalizeAttempt($attemptId, 'success', ['http_status' => $resp['status'], 'readback_ok' => true]);
            $this->evidence->appendLog("WRITE_OK op={$operation} status=" . $resp['status']);
            return ['ok' => true, 'outcome' => 'success', 'http_status' => $resp['status']];
        }

        // HTTP error consumes the reserved attempt and halts the batch; no automatic continuation.
        $rb = $operation === 'create'
            ? $this->snapshotAccountList($operation . '_' . $attemptId . '_after_error')
            : $this->readback($accountId, $operation . '_' . $attemptId . '_after_error');
        $this->gate->finalizeAttempt(
            $attemptId,
            'http_error',
            ['http_status' => $resp['status'], 'readback_ok' => $rb['ok']],
            "{$operation}:http_error:{$resp['status']}"
        );
        $this->evidence->appendLog("WRITE_HTTP_ERROR op={$operation} status=" . $resp['status'] . ' — BATCH_HALTED');
        return [
            'ok' => false,
            'outcome' => 'http_error',
            'http_status' => $resp['status'],
            'message' => 'mutation rejected by server',
            'stop' => true,
            'readback_ok' => $rb['ok'],
        ];
    }

    /** @return array{ok:bool, balance?:mixed, transactions?:mixed, status?:int, err?:string} */
    private function readback(int $accountId, string $label): array
    {
        $bal = $this->http->get('/api/voucher/balance/' . $accountId, [], true);
        $tx = $this->http->get('/api/voucher/transactions/' . $accountId, ['pageNumber' => 0], true);
        $this->evidence->writeRaw("account_{$accountId}_balance_{$label}.json", $bal['body']);
        $this->evidence->writeRaw("account_{$accountId}_transactions_{$label}.json", $tx['body']);
        if ($bal['status'] < 200 || $bal['status'] >= 300) {
            return ['ok' => false, 'status' => $bal['status'], 'err' => $bal['err'] ?: 'balance_failed'];
        }
        if ($tx['status'] < 200 || $tx['status'] >= 300) {
            return ['ok' => false, 'status' => $tx['status'], 'err' => $tx['err'] ?: 'tx_failed'];
        }
        return [
            'ok' => true,
            'balance' => json_decode($bal['body'], true),
            'transactions' => json_decode($tx['body'], true),
        ];
    }

    /** @param array<string, mixed> $payload */
    private function extractPayloadAccountId(array $payload): ?int
    {
        foreach ($payload as $key => $value) {
            $normalized = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $key) ?? '');
            if ($normalized === 'accountid' && (is_int($value) || (is_string($value) && ctype_digit($value)))) {
                return (int) $value;
            }
        }
        return null;
    }

    /** @return array{ok:bool, status?:int, err?:string} */
    private function snapshotAccountList(string $label): array
    {
        $r = $this->http->get('/api/account', [], true);
        $this->evidence->writeRaw("account_list_{$label}.json", $r['body']);
        if ($r['status'] < 200 || $r['status'] >= 300) {
            return ['ok' => false, 'status' => $r['status'], 'err' => $r['err'] ?: 'account_list_failed'];
        }
        return ['ok' => true, 'status' => $r['status']];
    }

}
