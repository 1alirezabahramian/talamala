<?php

declare(strict_types=1);

namespace Talamala\Domain\Payment;

final class PaymentContract
{
    /**
     * @param list<string> $remainingUnknowns
     * @param array<string, mixed> $gateway
     * @param list<string> $evidenceRefs
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $status,
        public readonly bool $livePaymentAuthorized,
        public readonly array $remainingUnknowns,
        public readonly array $gateway,
        public readonly ?string $ownerPolicyRef,
        public readonly array $evidenceRefs,
        public readonly array $raw,
    ) {}

    public static function notGrounded(): self
    {
        return new self('NOT_GROUNDED', false, ['Merchant contract', 'Sandbox process', 'Callback rules'], [], null, [], []);
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
        $gateway = is_array($data['gateway'] ?? null) ? $data['gateway'] : [];
        $evidence = is_array($data['evidence_refs'] ?? null) ? $data['evidence_refs'] : [];
        $ownerRef = isset($data['owner_policy_ref']) && is_string($data['owner_policy_ref'])
            ? trim($data['owner_policy_ref'])
            : null;

        return new self(
            (string) ($data['status'] ?? 'NOT_GROUNDED'),
            !empty($data['live_payment_authorized']),
            array_values(array_map('strval', $unknowns)),
            $gateway,
            $ownerRef !== '' ? $ownerRef : null,
            array_values(array_filter(array_map('strval', $evidence), static fn (string $v): bool => trim($v) !== '')),
            $data,
        );
    }

    public function isGrounded(): bool
    {
        return $this->status === 'GROUNDED';
    }

    public function assertCaptureAllowed(): void
    {
        if (!$this->isGrounded() || !$this->livePaymentAuthorized) {
            throw new \RuntimeException('Payment capture blocked (GT-006); contract NOT_GROUNDED or not authorized');
        }
        if ($this->remainingUnknowns !== []) {
            throw new \RuntimeException('Payment capture blocked: remaining_unknowns must be empty');
        }
        if ($this->ownerPolicyRef === null || $this->evidenceRefs === []) {
            throw new \RuntimeException('Payment capture blocked: Owner policy and evidence references are required');
        }
        foreach ([
            'name',
            'merchant_contract_path_or_url',
            'sandbox_base_url',
            'production_base_url',
            'callback_model',
            'signature_verification',
            'refund_model',
            'reverse_model',
        ] as $key) {
            if (!array_key_exists($key, $this->gateway) || $this->gateway[$key] === null || $this->gateway[$key] === '') {
                throw new \RuntimeException('Payment capture blocked: incomplete gateway.' . $key);
            }
        }
    }
}
