<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

final class FakeKimiaCreateCustomerClient implements KimiaCreateCustomerClient
{
    private int $seq = 90000;
    /** @var list<array<string, mixed>> */
    public array $calls = [];

    public function __construct(private readonly KimiaCreateCustomerContract $contract) {}

    public function create(array $payload): KimiaCreateCustomerResult
    {
        $this->contract->assertPayloadKeys($payload);
        $this->calls[] = $payload;
        $id = ++$this->seq;
        $field = $this->contract->successIdField ?? 'AccountId';
        return new KimiaCreateCustomerResult($this->contract->successHttpStatus ?? 200, $id, [$field => $id], (string) $this->contract->path);
    }
}
