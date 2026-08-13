<?php

declare(strict_types=1);

namespace Talamala\Domain\Identity;

/**
 * Staff: username + password.
 * Mandatory password change on first login.
 * Never universal admin/admin.
 */
interface StaffAuthService
{
    public function attemptLogin(
        string $tenantId,
        string $username,
        string $password,
    ): StaffAuthResult;

    public function rotatePassword(
        string $tenantId,
        string $staffId,
        string $currentPassword,
        string $newPassword,
    ): void;
}
