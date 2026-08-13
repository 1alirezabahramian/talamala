<?php

declare(strict_types=1);

namespace Talamala\Http\Controllers\Auth;

use Talamala\Application\Identity\StaffAuthApplicationService;
use Talamala\Domain\Tenant\Tenant;

final class StaffAuthController
{
    public function __construct(
        private readonly StaffAuthApplicationService $staffAuth,
    ) {}

    /** POST /v1/auth/staff/login */
    public function login(Tenant $tenant, array $body, string $correlationId): array
    {
        $username = (string) ($body['username'] ?? '');
        $password = (string) ($body['password'] ?? '');
        if ($username === '' || $password === '') {
            return ['status' => 422, 'body' => ['error' => 'credentials_required']];
        }

        $result = $this->staffAuth->attemptLogin($tenant->id, $username, $password, $correlationId);
        if (!$result->success) {
            return ['status' => 401, 'body' => ['error' => $result->errorCode]];
        }

        return [
            'status' => 200,
            'body' => [
                'access_token' => $result->sessionToken,
                'staff_id' => $result->staffId,
                'must_change_password' => $result->mustChangePassword,
            ],
        ];
    }

    /** POST /v1/auth/staff/password/rotate */
    public function rotatePassword(Tenant $tenant, string $staffId, array $body, string $correlationId): array
    {
        $current = (string) ($body['current_password'] ?? '');
        $new = (string) ($body['new_password'] ?? '');
        $result = $this->staffAuth->rotatePassword($tenant->id, $staffId, $current, $new, $correlationId);
        if (!$result->success) {
            return ['status' => 400, 'body' => ['error' => $result->errorCode]];
        }
        return ['status' => 200, 'body' => ['status' => 'password_updated']];
    }
}
