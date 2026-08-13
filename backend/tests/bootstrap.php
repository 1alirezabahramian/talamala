<?php

declare(strict_types=1);

$vendor = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendor)) {
    require_once $vendor;
} else {
    require_once __DIR__ . '/../app/bootstrap_autoload.php';
}
