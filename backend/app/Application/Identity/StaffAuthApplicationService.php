<?php

declare(strict_types=1);

namespace Talamala\Application\Identity;

use Talamala\Domain\Audit\AuditEvent;
use Talamala\Domain\Audit\AuditLogger;
use Talamala\Domain\Identity\StaffAuthResult;

/**
 * Stage 2 — staff username/password + mandatory first-login rotation.
 * Never ship universal admin/admin.
 */
final class StaffAuthApplicationService
{
    /** @var array<string, array{id:string,username:string,passwordHash:string,mustChange:bool}> */
    private array $staff = [];

    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function bootstrapStaff(
        string $tenantId,
        string $staffId,
        string $username,
        string $plainPassword,
        bool $mustChangePassword = true,
    ): void {
        $key = $tenantId . ':' . strtolower($username);
        $this->staff[$key] = [
            'id' => $staffId,
            'username' => $username,
            'passwordHash' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'mustChange' => $mustChangePassword,
        ];
    }

    public function attemptLogin(
        string $tenantId,
        string $username,
        string $password,
        string $correlationId,
    ): StaffAuthResult {
        $key = $tenantId . ':' . strtolower($username);
        $row = $this->staff[$key] ?? null;
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        if ($row === null || !password_verify($password, $row['passwordHash'])) {
            $this->audit->log(new AuditEvent(
                id: bin2hex(random_bytes(8)),
                tenantId: $tenantId,
                actorId: null,
                actorType: 'staff',
                action: 'staff.login_failed',
                targetType: 'username',
                targetId: $username,
                reason: 'invalid_credentials',
                correlationId: $correlationId,
                metadata: [],
                occurredAt: $now,
            ));
            return new StaffAuthResult(false, errorCode: 'invalid_credentials');
        }

        $token = bin2hex(random_bytes(24));
        $this->audit->log(new AuditEvent(
            id: bin2hex(random_bytes(8)),
            tenantId: $tenantId,
            actorId: $row['id'],
            actorType: 'staff',
            action: 'staff.login_success',
            targetType: 'staff',
            targetId: $row['id'],
            reason: $row['mustChange'] ? 'must_change_password' : null,
            correlationId: $correlationId,
            metadata: [],
            occurredAt: $now,
        ));

        return new StaffAuthResult(
            success: true,
            sessionToken: $token,
            staffId: $row['id'],
            mustChangePassword: $row['mustChange'],
        );
    }

    public function rotatePassword(
        string $tenantId,
        string $staffId,
        string $currentPassword,
        string $newPassword,
        string $correlationId,
    ): StaffAuthResult {
        $foundKey = null;
        $row = null;
        foreach ($this->staff as $k => $s) {
            if (str_starts_with($k, $tenantId . ':') && $s['id'] === $staffId) {
                $foundKey = $k;
                $row = $s;
                break;
            }
        }

        if ($row === null || !password_verify($currentPassword, $row['passwordHash'])) {
            return new StaffAuthResult(false, errorCode: 'invalid_current_password');
        }

        if (strlen($newPassword) < 10) {
            return new StaffAuthResult(false, errorCode: 'password_too_weak');
        }

        if (password_verify($newPassword, $row['passwordHash'])) {
            return new StaffAuthResult(false, errorCode: 'password_reuse');
        }

        $row['passwordHash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $row['mustChange'] = false;
        $this->staff[$foundKey] = $row;

        $this->audit->log(new AuditEvent(
            id: bin2hex(random_bytes(8)),
            tenantId: $tenantId,
            actorId: $staffId,
            actorType: 'staff',
            action: 'staff.password_rotated',
            targetType: 'staff',
            targetId: $staffId,
            reason: 'first_login_or_user_request',
            correlationId: $correlationId,
            metadata: [],
            occurredAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        ));

        return new StaffAuthResult(true, staffId: $staffId, mustChangePassword: false);
    }
}
