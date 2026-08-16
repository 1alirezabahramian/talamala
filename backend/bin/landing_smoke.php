<?php

declare(strict_types=1);

/**
 * Landing hub file + router wiring checks.
 * php backend/bin/landing_smoke.php
 */

$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok, mixed $d = null) use (&$pass, &$fail): void {
    if ($ok) {
        echo "OK  $name\n";
        $pass++;
    } else {
        echo "FAIL $name " . json_encode($d) . "\n";
        $fail++;
    }
};

$root = dirname(__DIR__);
$landing = $root . '/public/landing.html';
$router = $root . '/public/router.php';
$html = is_file($landing) ? file_get_contents($landing) : '';
$rt = is_file($router) ? file_get_contents($router) : '';

$check('landing_exists', is_file($landing), $landing);
$check('landing_has_customer_app', str_contains((string) $html, '/app/customer/'), null);
$check('landing_has_backoffice_app', str_contains((string) $html, '/app/backoffice/'), null);
$check('landing_has_otp_demo', str_contains((string) $html, '/otp-demo.html'), null);
$check('landing_has_healthz', str_contains((string) $html, '/healthz'), null);
$check('landing_rtl_fa', str_contains((string) $html, 'lang="fa"') && str_contains((string) $html, 'dir="rtl"'), null);
$check('router_serves_landing', str_contains((string) $rt, 'landing.html'), null);
$check('operators_doc', is_file(dirname($root) . '/docs/00-master/OPERATORS.md'), null);

$ver = dirname($root) . '/VERSION';
$check('version_file', is_file($ver) && trim((string) file_get_contents($ver)) !== '', $ver);
$html = (string) $html;
$check('landing_version_placeholder', str_contains($html, '{{VERSION}}') && str_contains($html, '{{BUILD_SHA}}'), null);
$rt = (string) $rt;
$check('router_html_security_headers', str_contains($rt, 'X-Frame-Options') && str_contains($rt, 'serveHtmlFile'), null);
$check('router_html_csp', str_contains($rt, 'Content-Security-Policy') && str_contains($rt, "frame-ancestors 'none'"), null);
$check('makefile_exists', is_file(dirname($root) . '/Makefile'), null);
$check('router_permissions_policy', str_contains($rt, 'Permissions-Policy'), null);
$robots = $root . '/public/robots.txt';
$rb = is_file($robots) ? (string) file_get_contents($robots) : '';
$check('robots_exists', is_file($robots), $robots);
$check('robots_blocks_dev_and_demos', str_contains($rb, '/v1/dev/') && str_contains($rb, '/otp-demo.html') && str_contains($rb, '/app/'), $rb);

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
