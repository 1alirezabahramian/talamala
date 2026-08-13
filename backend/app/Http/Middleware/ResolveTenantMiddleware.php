<?php

declare(strict_types=1);

namespace Talamala\Http\Middleware;

use Talamala\Domain\Tenant\TenantResolver;
use Talamala\Domain\Tenant\TenantResolutionException;

/**
 * Stage 1 foundation.
 * Resolves tenant from Host and binds it to request context.
 * Fail-closed: unknown/inactive/unverified → 403/404.
 */
final class ResolveTenantMiddleware
{
    public function __construct(
        private readonly TenantResolver $resolver,
    ) {}

    public function handle(object $request, callable $next): mixed
    {
        $host = method_exists($request, 'getHost')
            ? $request->getHost()
            : ($_SERVER['HTTP_HOST'] ?? '');

        try {
            $tenant = $this->resolver->resolveFromHost((string) $host);
        } catch (TenantResolutionException $e) {
            return $this->reject($e->getCode() ?: 403, $e->getMessage());
        }

        // Bind to request attributes / container for downstream use.
        if (method_exists($request, 'attributes')) {
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }

    private function reject(int $status, string $message): array
    {
        return [
            'status' => $status,
            'body' => [
                'error' => 'tenant_resolution_failed',
                'message' => $message,
            ],
        ];
    }
}
