<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia\Verify;

/**
 * Minimal HTTP helper for verification only.
 * Does not replace HttpKimiaReadClient.
 */
final class KimiaVerifyHttp
{
    public function __construct(
        private readonly KimiaVerifyConfig $config,
    ) {}

    /**
     * @param array<string, scalar|null> $query
     * @return array{status:int, body:string, err:string, timed_out:bool}
     */
    public function get(string $pathOrUrl, array $query = [], bool $auth = true): array
    {
        $url = $this->resolveUrl($pathOrUrl, $query);
        if ($auth && !$this->sameBaseHost($url)) {
            return ['status' => 0, 'body' => '', 'err' => 'authenticated_cross_host_get_rejected', 'timed_out' => false];
        }
        return $this->request('GET', $url, null, $auth);
    }

    /**
     * POST only when caller already passed WriteGate.
     * @return array{status:int, body:string, err:string, timed_out:bool}
     */
    public function post(string $pathOrUrl, ?string $jsonBody, bool $auth = true): array
    {
        // Defense in depth: credentials must never be POSTed to an arbitrary absolute URL.
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return ['status' => 0, 'body' => '', 'err' => 'absolute_post_url_rejected', 'timed_out' => false];
        }
        if (!str_starts_with($pathOrUrl, '/')) {
            return ['status' => 0, 'body' => '', 'err' => 'relative_post_path_must_start_with_slash', 'timed_out' => false];
        }
        $url = $this->resolveUrl($pathOrUrl, []);
        return $this->request('POST', $url, $jsonBody, $auth);
    }

    /**
     * @param array<string, scalar|null> $query
     */
    private function resolveUrl(string $pathOrUrl, array $query): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            $url = $pathOrUrl;
        } else {
            $url = $this->config->baseUrl . (str_starts_with($pathOrUrl, '/') ? $pathOrUrl : '/' . $pathOrUrl);
        }
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }
        return $url;
    }

    private function sameBaseHost(string $url): bool
    {
        $baseHost = strtolower((string) (parse_url($this->config->baseUrl, PHP_URL_HOST) ?: ''));
        $targetHost = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        return $baseHost !== '' && $targetHost !== '' && hash_equals($baseHost, $targetHost);
    }

    /**
     * @return array{status:int, body:string, err:string, timed_out:bool}
     */
    private function request(string $method, string $url, ?string $jsonBody, bool $auth): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['status' => 0, 'body' => '', 'err' => 'curl_init_failed', 'timed_out' => false];
        }
        $headers = ['Accept: application/json'];
        if ($jsonBody !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(15, $this->config->timeoutSeconds),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($auth) {
            $opts[CURLOPT_USERPWD] = $this->config->username . ':' . $this->config->password;
            $opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }
        if ($jsonBody !== null) {
            $opts[CURLOPT_POSTFIELDS] = $jsonBody;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        $timedOut = $errno === CURLE_OPERATION_TIMEDOUT
            || $errno === 28
            || str_contains(strtolower($err), 'timed out');

        if ($errno !== 0) {
            return [
                'status' => 0,
                'body' => '',
                'err' => $err !== '' ? $err : ('curl_' . $errno),
                'timed_out' => $timedOut,
            ];
        }

        return [
            'status' => $status,
            'body' => is_string($body) ? $body : '',
            'err' => '',
            'timed_out' => $timedOut,
        ];
    }
}
