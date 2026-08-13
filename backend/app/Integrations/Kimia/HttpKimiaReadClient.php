<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * Stage 3 start — Read-only HTTP client against Kimia OpenAPI.
 * Uses only confirmed GET paths from archived swagger (SHA ea3de1aa…).
 * Controllers must not call this directly; go through application services.
 *
 * Auth: Basic (credentials from env — never logged).
 * Query param for account list is Type (not accountType).
 * Groups uses accountType.
 */
final class HttpKimiaReadClient implements KimiaReadClient
{
    private readonly string $baseUrl;

    public function __construct(
        string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeoutSeconds = 30,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function listAccounts(?int $type = null): array
    {
        $query = [];
        if ($type !== null) {
            $query['Type'] = $type;
        }
        return $this->getJson('/api/account', $query);
    }

    public function listAccountGroups(int $accountType): array
    {
        return $this->getJson('/api/account/groups', ['accountType' => $accountType]);
    }

    public function getBalance(int $accountId, bool $includePeaks = false): array
    {
        $query = [];
        if ($includePeaks) {
            $query['includePeaks'] = 'true';
        }
        return $this->getJson('/api/voucher/balance/' . $accountId, $query);
    }

    public function getTransactions(int $accountId, array $query = []): array
    {
        return $this->getJson('/api/voucher/transactions/' . $accountId, $query);
    }

    public function listCoins(): array
    {
        return $this->getJson('/api/product/coins');
    }

    public function listCurrencies(): array
    {
        return $this->getJson('/api/product/currencies');
    }

    /**
     * @param array<string, scalar|null> $query
     * @return array<mixed>
     */
    private function getJson(string $path, array $query = []): array
    {
        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new KimiaTransportException('curl_init failed');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) {
            throw new KimiaTransportException('Kimia connection error: ' . $error, $errno);
        }

        if ($status === 401 || $status === 403) {
            throw new KimiaAuthException('Kimia authentication failed', $status);
        }

        if ($status >= 400) {
            throw new KimiaHttpException('Kimia HTTP ' . $status, $status, is_string($body) ? $body : null);
        }

        if ($body === false || $body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new KimiaUnexpectedResponseException('Non-JSON Kimia response');
        }

        return $decoded;
    }
}
