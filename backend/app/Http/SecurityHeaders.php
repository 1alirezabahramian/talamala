<?php

declare(strict_types=1);

namespace Talamala\Http;

/**
 * Baseline security / CORS headers for JSON API responses.
 */
final class SecurityHeaders
{
    /**
     * @return array<string, string>
     */
    public static function defaults(?string $requestOrigin = null): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'no-store',
            'Content-Type' => 'application/json; charset=utf-8',
        ];

        $allowed = getenv('TALAMALA_CORS_ORIGINS') ?: '';
        $allowedList = array_values(array_filter(array_map('trim', explode(',', $allowed))));
        if ($requestOrigin && in_array($requestOrigin, $allowedList, true)) {
            $headers['Access-Control-Allow-Origin'] = $requestOrigin;
            $headers['Access-Control-Allow-Headers'] = 'Authorization, Content-Type, Idempotency-Key, X-Correlation-Id, X-Customer-Id, X-Staff-Id, X-Talamala-Host, X-Talamala-Dev';
            $headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS';
            $headers['Vary'] = 'Origin';
        }

        return $headers;
    }
}
