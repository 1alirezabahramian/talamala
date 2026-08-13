<?php

declare(strict_types=1);

/**
 * Route map Stages 1–3. Tenant from Host middleware only.
 */

use Talamala\Http\Controllers\HealthController;
use Talamala\Http\Controllers\Auth\CustomerOtpController;
use Talamala\Http\Controllers\Auth\StaffAuthController;
use Talamala\Http\Controllers\Customer\CustomerAssetsController;

/** @var \Illuminate\Routing\Router $router */

$router->get('/healthz', [HealthController::class, 'live']);
$router->get('/readyz', [HealthController::class, 'ready']);

$router->post('/v1/auth/customer/otp/request', [CustomerOtpController::class, 'requestOtp']);
$router->post('/v1/auth/customer/otp/verify', [CustomerOtpController::class, 'verifyOtp']);
$router->post('/v1/auth/staff/login', [StaffAuthController::class, 'login']);
$router->post('/v1/auth/staff/password/rotate', [StaffAuthController::class, 'rotatePassword']);

// Customer (authenticated)
$router->get('/v1/customer/assets', [CustomerAssetsController::class, 'assets']);

// Admin
$router->get('/v1/admin/registrations', [\Talamala\Http\Controllers\Admin\RegistrationQueueController::class, 'index']);
$router->post('/v1/admin/registrations/{id}/approve', [\Talamala\Http\Controllers\Admin\RegistrationQueueController::class, 'approve']);
