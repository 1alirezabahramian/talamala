<?php

declare(strict_types=1);

namespace Talamala\Integrations\Jibit;

/**
 * Test double. Production uses official Jibit Identicator v1.5.2 HTTP client.
 */
final class FakeJibitIdentityClient implements JibitIdentityClient
{
    /** @var array<string, bool> "nationalCode:mobile" => matched */
    private array $matches = [];

    public function allowMatch(string $nationalCode, string $mobile): void
    {
        $this->matches[$nationalCode . ':' . $mobile] = true;
    }

    public function matchNationalCodeWithMobile(string $nationalCode, string $mobile): JibitMatchResult
    {
        $key = $nationalCode . ':' . $mobile;
        if (!empty($this->matches[$key])) {
            return new JibitMatchResult(true, providerReference: 'fake-jibit-' . md5($key), rawStatus: 'MATCHED');
        }
        return new JibitMatchResult(false, errorCode: 'no_match');
    }
}
