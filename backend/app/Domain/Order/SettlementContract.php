<?php

declare(strict_types=1);

namespace Talamala\Domain\Order;

/**
 * GT-005 machine contract. Default NOT_GROUNDED.
 * Grounded flags alone are insufficient: policy/evidence refs, complete semantics,
 * complete Kimia side-effect model, and zero remaining unknowns are required.
 */
final class SettlementContract
{
    /**
     * @param list<string> $remainingUnknowns
     * @param array<string, mixed> $semantics
     * @param array<string, mixed> $kimiaSideEffects
     * @param list<string> $evidenceRefs
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $status,
        public readonly bool $liveSettlementAuthorized,
        public readonly bool $orderToKimiaWireAllowed,
        public readonly array $remainingUnknowns,
        public readonly array $semantics,
        public readonly array $kimiaSideEffects,
        public readonly ?string $ownerPolicyRef,
        public readonly array $evidenceRefs,
        public readonly array $raw,
    ) {}

    public static function notGrounded(): self
    {
        return new self(
            'NOT_GROUNDED',
            false,
            false,
            ['Owner hold/freeze/credit rules', 'Kimia settlement side-effects', 'Reconciliation authority'],
            [],
            [],
            null,
            [],
            [],
        );
    }

    public static function fromJsonFile(string $path): self
    {
        if (!is_file($path)) {
            return self::notGrounded();
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            return self::notGrounded();
        }

        $unknowns = is_array($data['remaining_unknowns'] ?? null) ? $data['remaining_unknowns'] : [];
        $semantics = is_array($data['semantics'] ?? null) ? $data['semantics'] : [];
        $effects = is_array($data['kimia_side_effects'] ?? null) ? $data['kimia_side_effects'] : [];
        $evidence = is_array($data['evidence_refs'] ?? null) ? $data['evidence_refs'] : [];
        $ownerRef = isset($data['owner_policy_ref']) && is_string($data['owner_policy_ref'])
            ? trim($data['owner_policy_ref'])
            : null;

        return new self(
            (string) ($data['status'] ?? 'NOT_GROUNDED'),
            !empty($data['live_settlement_authorized']),
            !empty($data['order_to_kimia_wire_allowed']),
            array_values(array_map('strval', $unknowns)),
            $semantics,
            $effects,
            $ownerRef !== '' ? $ownerRef : null,
            array_values(array_filter(array_map('strval', $evidence), static fn (string $v): bool => trim($v) !== '')),
            $data,
        );
    }

    public function isGrounded(): bool
    {
        return $this->status === 'GROUNDED';
    }

    /** @throws \RuntimeException */
    public function assertWireAllowed(): void
    {
        if (!$this->isGrounded()) {
            throw new \RuntimeException('Settlement contract NOT_GROUNDED (GT-005); Order→Kimia wire forbidden');
        }
        if (!$this->liveSettlementAuthorized || !$this->orderToKimiaWireAllowed) {
            throw new \RuntimeException('Settlement wire default-deny until explicitly authorized');
        }
        if ($this->remainingUnknowns !== []) {
            throw new \RuntimeException('Settlement wire blocked: remaining_unknowns must be empty');
        }
        if ($this->ownerPolicyRef === null || $this->evidenceRefs === []) {
            throw new \RuntimeException('Settlement wire blocked: Owner policy and evidence references are required');
        }

        foreach (['hold', 'freeze', 'release', 'credit_trading', 'reconciliation'] as $key) {
            if (!array_key_exists($key, $this->semantics) || $this->semantics[$key] === null || $this->semantics[$key] === '') {
                throw new \RuntimeException('Settlement wire blocked: incomplete semantics.' . $key);
            }
        }
        foreach (['on_accept_order', 'on_settle', 'on_fail', 'balance_model'] as $key) {
            if (!array_key_exists($key, $this->kimiaSideEffects) || $this->kimiaSideEffects[$key] === null || $this->kimiaSideEffects[$key] === '') {
                throw new \RuntimeException('Settlement wire blocked: incomplete kimia_side_effects.' . $key);
            }
        }
    }

    public function publicSettlementStatus(): string
    {
        return 'blocked_by_ground_truth';
    }
}
