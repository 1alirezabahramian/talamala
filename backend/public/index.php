<?php

declare(strict_types=1);

/**
 * Minimal front controller — Stage 1–3 bootstrap without full Laravel.
 * Tenant from Host / X-Talamala-Host only.
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Http\Kernel;

header('Content-Type: application/json; charset=utf-8');
header('X-Talamala-Bootstrap', 'kernel-v1');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_')) {
        $name = strtolower(str_replace('_', '-', substr($k, 5)));
        $headers[$name] = is_string($v) ? $v : (string) $v;
    }
}
if (isset($_SERVER['HTTP_HOST'])) {
    $headers['host'] = $_SERVER['HTTP_HOST'];
}

$raw = file_get_contents('php://input') ?: '';
$jsonBody = null;
if ($raw !== '') {
    $decoded = json_decode($raw, true);
    $jsonBody = is_array($decoded) ? $decoded : null;
}

$kernel = new Kernel();
$result = $kernel->handle($method, $path, $headers, $jsonBody);

http_response_code((int) ($result['status'] ?? 500));
echo json_encode($result['body'] ?? new stdClass(), JSON_UNESCAPED_UNICODE);
