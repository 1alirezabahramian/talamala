<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * HTTP Write client — contracts from live Batch V1 (run 32197791006).
 * Does not enable itself; caller supplies credentials. No auto-retry.
 */
final class HttpKimiaWriteClient implements KimiaWriteClient
{
    private const PATH_EXCHANGE_GOLD = '/api/voucher/exchangegold';
    private const PATH_TRADE_CASH = '/api/voucher/tradecash';
    private const ACTION_BUY = 32;
    private const ACTION_SELL = 64;
    private const ACTION_RECEIVE = 2;
    private const ACTION_PAY = 4;

    private readonly string $baseUrl;

    public function __construct(
        string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeoutSeconds = 30,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function buyGold(int $accountId, string $goldPriceRialPerGram, string $valueGrams, string $requestId, int $goldUnit = 1): KimiaWriteResult
    {
        return $this->exchangeGold($accountId, self::ACTION_BUY, 'buy', $goldPriceRialPerGram, $valueGrams, $requestId, $goldUnit);
    }

    public function sellGold(int $accountId, string $goldPriceRialPerGram, string $valueGrams, string $requestId, int $goldUnit = 1): KimiaWriteResult
    {
        return $this->exchangeGold($accountId, self::ACTION_SELL, 'sell', $goldPriceRialPerGram, $valueGrams, $requestId, $goldUnit);
    }

    public function receiveCash(int $accountId, string $valueRial, string $requestId): KimiaWriteResult
    {
        return $this->tradeCash($accountId, self::ACTION_RECEIVE, 'receive', $valueRial, $requestId);
    }

    public function payCash(int $accountId, string $valueRial, string $requestId): KimiaWriteResult
    {
        return $this->tradeCash($accountId, self::ACTION_PAY, 'pay', $valueRial, $requestId);
    }

    private function exchangeGold(int $accountId, int $action, string $operation, string $goldPrice, string $value, string $requestId, int $goldUnit): KimiaWriteResult
    {
        $this->assertAccountId($accountId);
        $this->assertPositiveDecimal($goldPrice, 'GoldPrice');
        $this->assertPositiveDecimal($value, 'Value');
        $this->assertRequestId($requestId);
        if (!in_array($goldUnit, [0, 1, 2, 3], true)) {
            throw new \InvalidArgumentException('GoldUnit must be one of 0,1,2,3');
        }

        return $this->postJson(
            self::PATH_EXCHANGE_GOLD,
            [
                'AccountId' => $accountId,
                'Action' => $action,
                'GoldPrice' => $goldPrice,
                'Value' => $value,
                'GoldUnit' => $goldUnit,
                'RequestId' => $requestId,
            ],
            $action,
            $operation,
            ['GoldPrice', 'Value'],
        );
    }

    private function tradeCash(int $accountId, int $action, string $operation, string $valueRial, string $requestId): KimiaWriteResult
    {
        $this->assertAccountId($accountId);
        $this->assertPositiveDecimal($valueRial, 'Value');
        $this->assertRequestId($requestId);

        return $this->postJson(
            self::PATH_TRADE_CASH,
            [
                'AccountId' => $accountId,
                'Action' => $action,
                'Value' => $valueRial,
                'RequestId' => $requestId,
            ],
            $action,
            $operation,
            ['Value'],
        );
    }

    private function assertAccountId(int $accountId): void
    {
        if ($accountId <= 0) {
            throw new \InvalidArgumentException('AccountId must be positive');
        }
    }

    private function assertPositiveDecimal(string $value, string $field): void
    {
        $value = trim($value);
        if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $value)) {
            throw new \InvalidArgumentException($field . ' must be a canonical decimal string');
        }
        if (preg_match('/^0(?:\.0+)?$/', $value)) {
            throw new \InvalidArgumentException($field . ' must be greater than zero');
        }
    }

    private function assertRequestId(string $requestId): void
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $requestId)) {
            throw new \InvalidArgumentException('RequestId must be a UUID v4');
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $decimalNumberFields
     */
    private function encodePayload(array $payload, array $decimalNumberFields): string
    {
        $tokens = [];
        foreach ($decimalNumberFields as $field) {
            if (!array_key_exists($field, $payload) || !is_string($payload[$field])) {
                throw new \InvalidArgumentException('Missing decimal field ' . $field);
            }
            $token = '__KIMIA_DECIMAL_' . $field . '__';
            $tokens[$token] = trim($payload[$field]);
            $payload[$field] = $token;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new KimiaUnexpectedResponseException('json_encode failed for Kimia write payload');
        }

        foreach ($tokens as $token => $decimal) {
            $quoted = json_encode($token);
            if (!is_string($quoted) || !str_contains($body, $quoted)) {
                throw new KimiaUnexpectedResponseException('decimal payload token replacement failed');
            }
            $body = str_replace($quoted, $decimal, $body);
        }
        return $body;
    }

    /** @param array<string, mixed> $payload @param list<string> $decimalNumberFields */
    private function postJson(string $path, array $payload, int $action, string $operation, array $decimalNumberFields = []): KimiaWriteResult
    {
        $url = $this->baseUrl . $path;
        $body = $this->encodePayload($payload, $decimalNumberFields);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new KimiaTransportException('curl_init failed');
        }
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

        if ($errno !== 0) {
            throw new KimiaTransportException('Kimia write connection error: ' . $error, $errno);
        }
        if ($status === 401 || $status === 403) {
            throw new KimiaAuthException('Kimia write authentication failed', $status);
        }
        if ($status < 200 || $status >= 300) {
            throw new KimiaHttpException('Kimia write HTTP ' . $status, $status, is_string($raw) ? $raw : null);
        }

        $decoded = null;
        $recordId = null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                if (preg_match('/^-?\d+$/', trim($raw))) {
                    $recordId = (int) trim($raw);
                } else {
                    throw new KimiaUnexpectedResponseException('Non-JSON Kimia write response');
                }
            } else {
                $recordId = $decoded['Id'] ?? $decoded['id'] ?? $decoded['ID'] ?? null;
                if (count($decoded) === 1) {
                    $only = reset($decoded);
                    if ($recordId === null && (is_int($only) || (is_string($only) && ctype_digit($only)))) {
                        $recordId = $only;
                    }
                }
            }
        }

        return new KimiaWriteResult(
            httpStatus: $status,
            recordId: is_int($recordId) || is_string($recordId) ? $recordId : null,
            rawDecoded: is_array($decoded) ? $decoded : null,
            endpoint: $path,
            action: $action,
            operation: $operation,
        );
    }
}
