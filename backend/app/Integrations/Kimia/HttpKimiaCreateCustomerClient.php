<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

final class HttpKimiaCreateCustomerClient implements KimiaCreateCustomerClient
{
    private readonly string $baseUrl;

    public function __construct(
        string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly KimiaCreateCustomerContract $contract,
        private readonly int $timeoutSeconds = 30,
    ) { $this->baseUrl = rtrim($baseUrl, '/'); }

    public function create(array $payload): KimiaCreateCustomerResult
    {
        $this->contract->assertGroundedForHttp();
        $this->contract->assertPayloadKeys($payload);
        $path = (string) $this->contract->path;
        $url = $this->baseUrl . $path;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) throw new KimiaUnexpectedResponseException('json_encode failed for create customer payload');

        $ch = curl_init($url);
        if ($ch === false) throw new KimiaTransportException('curl_init failed');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(15, $this->timeoutSeconds),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) throw new KimiaTransportException('Kimia create connection error: ' . $error, $errno);
        if ($status === 401 || $status === 403) throw new KimiaAuthException('Kimia create authentication failed', $status);
        $expected = $this->contract->successHttpStatus;
        if ($expected !== null) {
            if ($status !== $expected) throw new KimiaHttpException('Kimia create HTTP ' . $status . ' (expected ' . $expected . ')', $status, is_string($raw) ? $raw : null);
        } elseif ($status < 200 || $status >= 300) {
            throw new KimiaHttpException('Kimia create HTTP ' . $status, $status, is_string($raw) ? $raw : null);
        }

        $decoded = null;
        $accountId = null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && $this->contract->successIdField) {
                $accountId = $decoded[$this->contract->successIdField] ?? null;
                if (!is_int($accountId) && !is_string($accountId)) throw new KimiaUnexpectedResponseException('Create Customer success id field missing from response');
            } elseif (preg_match('/^-?\d+$/', trim($raw))) {
                $accountId = (int) trim($raw);
            } elseif (!is_array($decoded)) {
                throw new KimiaUnexpectedResponseException('Unexpected Create Customer success response');
            }
        }

        return new KimiaCreateCustomerResult($status, is_int($accountId) || is_string($accountId) ? $accountId : null, is_array($decoded) ? $decoded : null, $path);
    }
}
