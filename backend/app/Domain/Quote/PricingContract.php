<?php

declare(strict_types=1);

namespace Talamala\Domain\Quote;

/**
 * Machine-readable GT-004 pricing contract.
 * NOT_GROUNDED until Owner archives official provider + coefficients/rounding/TTL policy.
 * Never invent provider behavior or business numbers in application code.
 */
final class PricingContract
{
    /**
     * @param list<string> $remainingUnknowns
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $status,
        public readonly bool $livePricingAuthorized,
        public readonly array $remainingUnknowns,
        public readonly array $raw,
    ) {}

    public static function notGrounded(): self
    {
        return new self(
            'NOT_GROUNDED',
            false,
            [
                'Official price provider contract document',
                'Owner-approved x/y/z coefficients and application order',
                'Rounding mode and decimal scales',
                'Authoritative quote TTL / freeze duration',
                'Failover and stale-price rejection rules',
            ],
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

        $unknowns = $data['remaining_unknowns'] ?? [];
        if (!is_array($unknowns)) {
            $unknowns = ['remaining_unknowns malformed'];
        }

        return new self(
            (string) ($data['status'] ?? 'NOT_GROUNDED'),
            ($data['live_pricing_authorized'] ?? false) === true,
            array_values(array_map('strval', $unknowns)),
            $data,
        );
    }

    public function isGrounded(): bool
    {
        return $this->status === 'GROUNDED';
    }

    /**
     * Live price fetch / quote issue from provider must call this before external pricing.
     *
     * @throws PriceProviderUnavailableException
     */
    public function assertLivePricingAllowed(): void
    {
        if (!$this->isGrounded()) {
            throw new PriceProviderUnavailableException(
                'Pricing contract is NOT_GROUNDED (GT-004). No live price feed.'
            );
        }
        if (!$this->livePricingAuthorized) {
            throw new PriceProviderUnavailableException(
                'Live pricing default-deny: live_pricing_authorized=false until Owner authorization archived.'
            );
        }
        if ($this->remainingUnknowns !== []) {
            throw new PriceProviderUnavailableException(
                'Pricing contract still contains unresolved GT-004 unknowns.'
            );
        }

        $provider = $this->raw['provider'] ?? null;
        if (!is_array($provider)
            || !is_string($provider['name'] ?? null) || trim((string) $provider['name']) === ''
            || !is_string($provider['official_api_doc_url_or_path'] ?? null) || trim((string) $provider['official_api_doc_url_or_path']) === ''
            || !is_string($provider['auth_model'] ?? null) || trim((string) $provider['auth_model']) === ''
            || !is_int($provider['freshness_sla_seconds'] ?? null) || $provider['freshness_sla_seconds'] <= 0
            || !is_string($provider['failover_policy'] ?? null) || trim((string) $provider['failover_policy']) === ''
            || !is_string($provider['observed_at_field'] ?? null) || trim((string) $provider['observed_at_field']) === ''
        ) {
            throw new PriceProviderUnavailableException(
                'Official provider/freshness/failover contract is incomplete; refusing live pricing.'
            );
        }

        $assets = $this->raw['assets_supported'] ?? null;
        if (!is_array($assets) || $assets === []) {
            throw new PriceProviderUnavailableException(
                'Release pricing asset scope is empty; refusing live pricing.'
            );
        }

        $coefficients = $this->raw['coefficients'] ?? null;
        if (!is_array($coefficients)
            || !is_string($coefficients['x'] ?? null) || $coefficients['x'] === ''
            || !is_string($coefficients['y'] ?? null) || $coefficients['y'] === ''
            || !is_string($coefficients['z'] ?? null) || $coefficients['z'] === ''
        ) {
            throw new PriceProviderUnavailableException(
                'Pricing coefficients x/y/z not Owner-supplied as decimal strings; refusing to invent.'
            );
        }

        $rounding = $this->raw['rounding'] ?? null;
        if (!is_array($rounding)
            || !is_string($rounding['order'] ?? null) || trim((string) $rounding['order']) === ''
            || !is_string($rounding['mode'] ?? null) || trim((string) $rounding['mode']) === ''
            || !is_int($rounding['scale_rial'] ?? null) || $rounding['scale_rial'] < 0
            || !is_int($rounding['scale_quantity'] ?? null) || $rounding['scale_quantity'] < 0
        ) {
            throw new PriceProviderUnavailableException(
                'Owner rounding order/mode/scales are incomplete; refusing live pricing.'
            );
        }

        $quote = $this->raw['quote_policy'] ?? null;
        if (!is_array($quote)
            || !is_int($quote['default_ttl_seconds'] ?? null) || $quote['default_ttl_seconds'] <= 0
            || !is_int($quote['max_ttl_seconds'] ?? null) || $quote['max_ttl_seconds'] < $quote['default_ttl_seconds']
            || !is_bool($quote['freeze_on_accept'] ?? null)
            || !is_string($quote['authority'] ?? null) || trim((string) $quote['authority']) === ''
        ) {
            throw new PriceProviderUnavailableException(
                'Authoritative quote TTL/freeze policy is incomplete (FA-047).'
            );
        }
    }
}
