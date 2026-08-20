<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

final class KimiaCreateCustomerContract
{
    /**
     * @param list<string> $requiredFields
     * @param list<string> $optionalFields
     * @param list<array<string, mixed>> $errorCatalog
     * @param list<string> $remainingUnknowns
     */
    public function __construct(
        public readonly bool $grounded,
        public readonly ?string $method,
        public readonly ?string $path,
        public readonly ?string $requestSchema,
        public readonly array $requiredFields,
        public readonly array $optionalFields,
        public readonly ?int $successHttpStatus,
        public readonly ?string $successIdField,
        public readonly array $errorCatalog,
        public readonly ?string $swaggerSha256,
        public readonly ?string $sourceNote,
        public readonly bool $liveCreateAuthorized = false,
        public readonly array $remainingUnknowns = [],
    ) {}

    public static function notGrounded(): self
    {
        return new self(
            false,
            null,
            null,
            null,
            [],
            [],
            null,
            null,
            [],
            'be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea',
            'GT-002 open — fill from live swagger extract only',
            false,
            [
                'Duplicate-customer detection/status/body semantics',
                'Validation error body/codes beyond generic HTTP 400',
                'Authoritative post-create readback',
            ],
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

        $required = $data['required_fields'] ?? null;
        $optional = $data['optional_fields'] ?? null;
        $grounded = ($data['status'] ?? '') === 'GROUNDED'
            && !empty($data['method'])
            && !empty($data['path'])
            && !empty($data['request_schema'])
            && is_array($required)
            && is_array($optional)
            && isset($data['success_http_status']);

        $unknowns = $data['remaining_unknowns'] ?? [];
        if (!is_array($unknowns)) {
            $unknowns = [];
        }

        return new self(
            $grounded,
            isset($data['method']) ? (string) $data['method'] : null,
            isset($data['path']) ? (string) $data['path'] : null,
            isset($data['request_schema']) ? (string) $data['request_schema'] : null,
            array_values(array_map('strval', is_array($required) ? $required : [])),
            array_values(array_map('strval', is_array($optional) ? $optional : [])),
            isset($data['success_http_status']) ? (int) $data['success_http_status'] : null,
            isset($data['success_id_field']) && $data['success_id_field'] !== null
                ? (string) $data['success_id_field']
                : null,
            is_array($data['error_catalog'] ?? null) ? $data['error_catalog'] : [],
            isset($data['swagger_sha256_live_preflight'])
                ? (string) $data['swagger_sha256_live_preflight']
                : null,
            isset($data['notes']) ? (string) $data['notes'] : null,
            !empty($data['live_create_authorized']),
            array_values(array_map('strval', $unknowns)),
        );
    }

    public function isGrounded(): bool
    {
        return $this->grounded;
    }

    public function assertGroundedForHttp(): void
    {
        if (!$this->grounded) {
            throw new KimiaContractNotGroundedException('Create Customer contract is not grounded (GT-002)');
        }
        if (strtoupper((string) $this->method) !== 'POST') {
            throw new KimiaContractNotGroundedException('Create Customer grounded contract must use POST');
        }
        $path = (string) $this->path;
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '://') || preg_match('/[\r\n?#]/', $path)) {
            throw new KimiaContractNotGroundedException('Create Customer grounded contract path must be a relative Kimia API path');
        }
        if ($this->requestSchema === null || $this->requestSchema === '') {
            throw new KimiaContractNotGroundedException('Create Customer request schema must be grounded');
        }
        if ($this->successHttpStatus === null) {
            throw new KimiaContractNotGroundedException('Create Customer success HTTP status must be grounded');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function assertPayloadKeys(array $payload): void
    {
        if (!$this->grounded) {
            throw new KimiaContractNotGroundedException('Create Customer contract is not grounded (GT-002)');
        }
        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new \InvalidArgumentException('Missing required Create Customer field: ' . $field);
            }
        }
        $allowed = array_flip(array_merge($this->requiredFields, $this->optionalFields));
        foreach (array_keys($payload) as $key) {
            if (!isset($allowed[$key])) {
                throw new \InvalidArgumentException('Unknown Create Customer field: ' . $key);
            }
        }
    }

    /** Documented unknowns stay explicit — never claim full GT-002 closure. */
    public function hasOpenUnknowns(): bool
    {
        return $this->remainingUnknowns !== [];
    }
}
