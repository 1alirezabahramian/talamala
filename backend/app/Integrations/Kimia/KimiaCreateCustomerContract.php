<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

final class KimiaCreateCustomerContract
{
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
    ) {}

    public static function notGrounded(): self
    {
        return new self(false, null, null, null, [], [], null, null, [], 'be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea', 'GT-002 open — fill from live swagger extract only');
    }

    public static function fromJsonFile(string $path): self
    {
        if (!is_file($path)) return self::notGrounded();
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) return self::notGrounded();
        $grounded = ($data['status'] ?? '') === 'GROUNDED' && !empty($data['method']) && !empty($data['path']) && !empty($data['required_fields']) && is_array($data['required_fields']);
        return new self(
            $grounded,
            isset($data['method']) ? (string) $data['method'] : null,
            isset($data['path']) ? (string) $data['path'] : null,
            isset($data['request_schema']) ? (string) $data['request_schema'] : null,
            array_values(array_map('strval', $data['required_fields'] ?? [])),
            array_values(array_map('strval', $data['optional_fields'] ?? [])),
            isset($data['success_http_status']) ? (int) $data['success_http_status'] : null,
            isset($data['success_id_field']) ? (string) $data['success_id_field'] : null,
            is_array($data['error_catalog'] ?? null) ? $data['error_catalog'] : [],
            isset($data['swagger_sha256_live_preflight']) ? (string) $data['swagger_sha256_live_preflight'] : null,
            isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }

    public function isGrounded(): bool { return $this->grounded; }

    public function assertGroundedForHttp(): void
    {
        if (!$this->grounded) throw new KimiaContractNotGroundedException('Create Customer contract is not grounded (GT-002)');
        if (strtoupper((string) $this->method) !== 'POST') throw new KimiaContractNotGroundedException('Create Customer grounded contract must use POST');
        $path = (string) $this->path;
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '://') || preg_match('/[\r\n?#]/', $path)) {
            throw new KimiaContractNotGroundedException('Create Customer grounded contract path must be a relative Kimia API path');
        }
    }

    public function assertPayloadKeys(array $payload): void
    {
        if (!$this->grounded) throw new KimiaContractNotGroundedException('Create Customer contract is not grounded (GT-002)');
        foreach ($this->requiredFields as $field) if (!array_key_exists($field, $payload)) throw new \InvalidArgumentException('Missing required Create Customer field: ' . $field);
        $allowed = array_flip(array_merge($this->requiredFields, $this->optionalFields));
        foreach (array_keys($payload) as $key) if (!isset($allowed[$key])) throw new \InvalidArgumentException('Unknown Create Customer field: ' . $key);
    }
}
