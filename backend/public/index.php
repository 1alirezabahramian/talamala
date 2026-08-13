<?php

declare(strict_types=1);

/**
 * Minimal front controller — Stage 1 bootstrap without full Laravel.
 * Tenant from Host header only. Financial numbers never computed here.
 */

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../app/bootstrap_autoload.php',
];
foreach ($autoloadCandidates as $a) {
    if (is_file($a)) {
        require_once $a;
        break;
    }
}

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Domain\Tenant\TenantResolutionException;
use Talamala\Http\Middleware\ResolveTenantMiddleware;
use Talamala\Infrastructure\Persistence\InMemoryTenantResolver;

header('Content-Type: application/json; charset=utf-8');
header('X-Talamala-Bootstrap', 'minimal-v1');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

// Health without tenant
if ($method === 'GET' && ($path === '/healthz' || $path === '/v1/healthz')) {
    echo json_encode(['status' => 'ok', 'service' => 'talamala', 'ts' => gmdate('c')]);
    exit;
}

$resolver = new InMemoryTenantResolver();
// Demo seed — replace with DB in Stage 1 production wiring
$resolver->register(new \Talamala\Domain\Tenant\Tenant(
    id: '00000000-0000-0000-0000-000000000001',
    slug: 'demo',
    primaryHost: 'demo.local',
    isActive: true,
    isVerified: true,
));

$host = $_SERVER['HTTP_HOST'] ?? '';
// Allow X-Forwarded-Host only in controlled deploy; default Host
if (!empty($_SERVER['HTTP_X_TALAMALA_HOST'])) {
    $host = $_SERVER['HTTP_X_TALAMALA_HOST'];
}

try {
    $tenant = $resolver->resolveFromHost($host);
} catch (TenantResolutionException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'tenant_unresolved', 'message' => $e->getMessage()]);
    exit;
}

// Ready
if ($method === 'GET' && ($path === '/readyz' || $path === '/v1/readyz')) {
    echo json_encode([
        'status' => 'ready',
        'tenant_id' => $tenant->id,
        'tenant_slug' => $tenant->slug,
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    'error' => 'not_found',
    'path' => $path,
    'tenant' => $tenant->slug,
    'hint' => 'Wire full router in Stage 2+; OTP/assets routes exist as classes',
]);
