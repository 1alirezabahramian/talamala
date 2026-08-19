<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * Create Customer / Account on Kimia — GT-002.
 * No method may invent path or body; contract must be grounded first.
 *
 * @param array<string, mixed> $payload keys must match grounded contract only
 */
interface KimiaCreateCustomerClient
{
    public function create(array $payload): KimiaCreateCustomerResult;
}
