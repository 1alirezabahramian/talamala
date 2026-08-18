<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia\Verify;

/**
 * Controlled Kimia verification orchestrator.
 * Default mode: read-only preflight + catalog extraction.
 * Mutations only when WriteGate passes and CLI explicitly requests a write op.
 */
final class KimiaVerifyRunner
{
    private bool $preflightOk = false;

    /** @var array<string, mixed> */
    private array $lastCatalog = [];

    public function __construct(
        private readonly KimiaVerifyConfig $config,
        private readonly KimiaVerifyEvidence $evidence,
        private readonly KimiaVerifyHttp $http,
        private readonly KimiaWriteGate $gate,
    ) {}

    public function preflightOk(): bool
    {
        return $this->preflightOk;
    }

    /** @return array<string, mixed> */
    public function lastCatalog(): array
    {
        return $this->lastCatalog;
    }

    /**
     * P0–P6 style read-only preflight. Never POSTs.
     * @return array{ok:bool, steps:array<string, mixed>}
     */
    public function runPreflight(int $baselineAccountId = 350): array
    {
        $steps = [];
        $this->preflightOk = false;

        if (!$this->config->hasReadCredentials()) {
            return $this->failPreflight($steps, 'env', 'KIMIA_BASE_URL/USERNAME/PASSWORD required');
        }

        $this->evidence->writeJson('config_public.json', $this->config->publicMeta());

        // P0 connectivity
        $r0 = $this->http->get($this->config->swaggerUrl, [], false);
        if ($r0['status'] === 0) {
            return $this->failPreflight($steps, 'P0_connectivity', $r0['err'] ?: 'unreachable');
        }
        $steps['P0_connectivity'] = ['ok' => true, 'http_status' => $r0['status']];

        // P1 live swagger
        $r1 = $r0;
        if ($r1['status'] < 200 || $r1['status'] >= 300 || $r1['body'] === '') {
            $r1 = $this->http->get($this->config->swaggerUrl, [], true);
        }
        if ($r1['status'] < 200 || $r1['status'] >= 300 || $r1['body'] === '') {
            return $this->failPreflight($steps, 'P1_live_swagger', 'HTTP ' . $r1['status']);
        }
        $this->evidence->writeRaw('swagger_live.json', $r1['body']);
        $catalog = KimiaSwaggerCatalog::analyze($r1['body']);
        $this->lastCatalog = $catalog;
        $this->evidence->writeJson('swagger_catalog.json', $catalog);
        $steps['P1_live_swagger'] = [
            'ok' => true,
            'version' => $catalog['version'],
            'sha256' => $catalog['sha256'],
        ];

        // P2 catalog / archive note (no hard-coded action mapping)
        $steps['P2_catalog'] = [
            'ok' => true,
            'write_related_paths' => count($catalog['paths']),
            'schemas' => count($catalog['schemas']),
            'action_enums_found' => count($catalog['action_enums']),
            'note' => 'Action codes must be mapped from live enums + Evidence, not historical 32/64/2/4 alone',
        ];

        // P3 auth
        $r3 = $this->http->get('/api/account', [], true);
        if ($r3['status'] === 401 || $r3['status'] === 403) {
            return $this->failPreflight($steps, 'P3_auth', 'HTTP ' . $r3['status']);
        }
        if ($r3['status'] < 200 || $r3['status'] >= 300) {
            return $this->failPreflight($steps, 'P3_auth', 'HTTP ' . $r3['status'] . ' ' . $r3['err']);
        }
        $this->evidence->writeRaw('account_list_raw.json', $r3['body']);
        $steps['P3_auth'] = ['ok' => true, 'http_status' => $r3['status']];

        // P4/P5/P6 baseline account
        $bal = $this->http->get('/api/voucher/balance/' . $baselineAccountId, [], true);
        if ($bal['status'] < 200 || $bal['status'] >= 300) {
            return $this->failPreflight($steps, 'P5_balance', 'HTTP ' . $bal['status']);
        }
        $this->evidence->writeRaw("account_{$baselineAccountId}_balance_before.json", $bal['body']);
        $steps['P5_balance'] = ['ok' => true, 'account_id' => $baselineAccountId];

        $tx = $this->http->get('/api/voucher/transactions/' . $baselineAccountId, ['pageNumber' => 0], true);
        if ($tx['status'] < 200 || $tx['status'] >= 300) {
            return $this->failPreflight($steps, 'P6_transactions', 'HTTP ' . $tx['status']);
        }
        $this->evidence->writeRaw("account_{$baselineAccountId}_transactions_before.json", $tx['body']);
        $steps['P6_transactions'] = ['ok' => true, 'account_id' => $baselineAccountId];

        $steps['P4_account'] = ['ok' => true, 'baseline_account_id' => $baselineAccountId, 'via' => 'balance_endpoint'];

        $this->preflightOk = true;
        $result = [
            'ok' => true,
            'ts' => gmdate('c'),
            'write_attempted' => false,
            'steps' => $steps,
            'swagger_sha256' => $catalog['sha256'],
            'swagger_version' => $catalog['version'],
        ];
        $this->evidence->writeJson('preflight_result.json', $result);
        $this->evidence->appendLog('PREFLIGHT_OK sha=' . $catalog['sha256']);
        return $result;
    }

    /**
     * Snapshot balance+tx for account (read-only).
     * @return array{ok:bool, balance?:mixed, transactions?:mixed, status?:int, err?:string}
     */
    public function readback(int $accountId, string $label): array
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

    /**
     * Execute one gated mutation attempt. Path/body come from Owner/live Swagger.
     * @param array<string, mixed> $payloadDecoded
     * @return array{ok:bool, outcome:string, http_status?:int, message?:string, stop?:bool}
     */
    public function mutateOnce(
        string $operation,
        int $accountId,
        string $path,
        array $payloadDecoded,
    ): array {
        $verifier = new KimiaMutationVerifier(
            $this->config,
            $this->evidence,
            $this->http,
            $this->gate,
            $this->lastCatalog,
        );
        return $verifier->mutateOnce($operation, $accountId, $path, $payloadDecoded, $this->preflightOk);
    }

    private function failPreflight(array $steps, string $step, string $msg): array
    {
        $steps[$step] = ['ok' => false, 'message' => $msg];
        $result = ['ok' => false, 'ts' => gmdate('c'), 'write_attempted' => false, 'steps' => $steps];
        $this->evidence->writeJson('preflight_result.json', $result);
        $this->evidence->appendLog("PREFLIGHT_FAIL {$step}: {$msg}");
        $this->preflightOk = false;
        return $result;
    }
}
