<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia\Verify;

/**
 * Default-deny gate for any Kimia mutation (POST).
 * Attempts are reserved BEFORE network send so a crash cannot make a sent Write retryable.
 * Any unresolved reservation or failure halts the current Owner batch until a new token is issued.
 */
final class KimiaWriteGate
{
    public function __construct(
        private readonly KimiaVerifyConfig $config,
        private readonly KimiaVerifyEvidence $evidence,
    ) {}

    /** @return array{ok:bool, reason?:string} */
    public function assertMayMutate(string $operation, int $accountId, bool $preflightOk): array
    {
        if (!$this->config->writeEnabled) {
            return ['ok' => false, 'reason' => 'KIMIA_WRITE_VERIFY_ENABLE is not 1'];
        }
        if ($this->config->expectedOwnerToken === '') {
            return ['ok' => false, 'reason' => 'KIMIA_WRITE_OWNER_TOKEN is required; no default token is accepted'];
        }
        if ($this->config->ownerAuthorization === ''
            || !hash_equals($this->config->expectedOwnerToken, $this->config->ownerAuthorization)) {
            return ['ok' => false, 'reason' => 'Owner authorization token missing or mismatch'];
        }
        if (!$preflightOk) {
            return ['ok' => false, 'reason' => 'fresh preflight not OK'];
        }

        $ops = ['buy', 'sell', 'receive', 'pay', 'create'];
        if (!in_array($operation, $ops, true)) {
            return ['ok' => false, 'reason' => 'unknown operation (out of batch scope)'];
        }

        // Create is the only operation that has no pre-existing target account id.
        if ($operation !== 'create') {
            if ($accountId <= 0) {
                return ['ok' => false, 'reason' => 'account_id required for account-targeted mutation'];
            }
            if ($this->config->accountAllowlist === []) {
                return ['ok' => false, 'reason' => 'empty account allowlist'];
            }
            if (!in_array($accountId, $this->config->accountAllowlist, true)) {
                return ['ok' => false, 'reason' => 'account_id not in allowlist'];
            }
        }

        $state = $this->loadState();
        if (!empty($state['halted'])) {
            return ['ok' => false, 'reason' => 'current Owner batch is halted: ' . (string) ($state['halt_reason'] ?? 'unknown')];
        }
        if ($this->hasPendingReservation($state)) {
            return ['ok' => false, 'reason' => 'unresolved reserved mutation attempt; Owner review/new batch token required'];
        }

        $consumed = (int) ($state['consumed'][$operation] ?? 0);
        $budget = (int) ($this->config->attemptBudget[$operation] ?? 0);
        if ($budget <= 0) {
            return ['ok' => false, 'reason' => "explicit attempt budget missing/zero for {$operation}"];
        }
        if ($consumed >= $budget) {
            return ['ok' => false, 'reason' => "attempt budget exhausted for {$operation} ({$consumed}/{$budget})"];
        }

        return ['ok' => true];
    }

    /** Reserve one mutation slot BEFORE HTTP send. */
    public function reserveAttempt(string $operation, int $accountId, string $path, string $attemptId): void
    {
        if (!preg_match('/^[a-f0-9]{16,64}$/', $attemptId)) {
            throw new \InvalidArgumentException('invalid attempt id');
        }
        $this->evidence->withExclusiveLock($this->lockFile(), function () use ($operation, $accountId, $path, $attemptId): void {
            $state = $this->loadState();
            if (!empty($state['halted']) || $this->hasPendingReservation($state)) {
                throw new \RuntimeException('batch halted or another mutation attempt is unresolved');
            }
            $budget = (int) ($this->config->attemptBudget[$operation] ?? 0);
            $consumed = (int) ($state['consumed'][$operation] ?? 0);
            if ($budget <= 0 || $consumed >= $budget) {
                throw new \RuntimeException('attempt budget exhausted before reserve');
            }
            $state['consumed'][$operation] = $consumed + 1;
            $state['outcomes'][] = [
                'attempt_id' => $attemptId,
                'ts' => gmdate('c'),
                'operation' => $operation,
                'account_id' => $accountId > 0 ? $accountId : null,
                'path' => $path,
                'outcome' => 'reserved_before_send',
            ];
            $this->saveState($state);
        });
    }

    /** @param array<string, mixed> $meta */
    public function finalizeAttempt(string $attemptId, string $outcome, array $meta = [], ?string $haltReason = null): void
    {
        $this->evidence->withExclusiveLock($this->lockFile(), function () use ($attemptId, $outcome, $meta, $haltReason): void {
            $state = $this->loadState();
            $updated = false;
            foreach ($state['outcomes'] as &$row) {
                if (is_array($row) && ($row['attempt_id'] ?? null) === $attemptId) {
                    $row = array_merge($row, $meta, [
                        'completed_ts' => gmdate('c'),
                        'outcome' => $outcome,
                    ]);
                    $updated = true;
                    break;
                }
            }
            unset($row);
            if (!$updated) {
                throw new \RuntimeException('cannot finalize unknown mutation attempt id');
            }
            if ($haltReason !== null) {
                $state['halted'] = true;
                $state['halt_reason'] = $haltReason;
                $state['halted_ts'] = gmdate('c');
            }
            $this->saveState($state);
        });
    }

    public function batchEvidencePrefix(): string
    {
        $fp = $this->config->ownerBatchFingerprint();
        return $fp === '' ? 'unauthorized' : substr($fp, 0, 16);
    }

    /** @return array<string, mixed> */
    private function loadState(): array
    {
        return $this->evidence->loadJson($this->stateFile(), [
            'batch_fingerprint' => $this->config->ownerBatchFingerprint(),
            'consumed' => [],
            'outcomes' => [],
            'halted' => false,
        ]);
    }

    /** @param array<string, mixed> $state */
    private function saveState(array $state): void
    {
        $this->evidence->writeJson($this->stateFile(), $state);
    }

    /** @param array<string, mixed> $state */
    private function hasPendingReservation(array $state): bool
    {
        foreach (($state['outcomes'] ?? []) as $row) {
            if (is_array($row) && ($row['outcome'] ?? null) === 'reserved_before_send') {
                return true;
            }
        }
        return false;
    }

    private function stateFile(): string
    {
        return 'state/write_attempt_state_' . $this->batchEvidencePrefix() . '.json';
    }

    private function lockFile(): string
    {
        return 'state/write_attempt_state_' . $this->batchEvidencePrefix() . '.lock';
    }
}
