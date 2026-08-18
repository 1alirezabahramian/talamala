<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia\Verify;

/**
 * Env-only config for controlled Kimia verification.
 * Secrets never logged. Write remains default-deny.
 */
final class KimiaVerifyConfig
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly string $username,
        public readonly string $password,
        public readonly string $swaggerUrl,
        public readonly string $evidenceDir,
        public readonly bool $writeEnabled,
        /** @var list<int> */
        public readonly array $accountAllowlist,
        public readonly string $ownerAuthorization,
        public readonly string $expectedOwnerToken,
        /** Remaining mutation attempts per operation key (buy|sell|receive|pay|create) */
        public readonly array $attemptBudget,
        public readonly int $timeoutSeconds,
    ) {}

    public static function fromEnv(string $repoRoot): self
    {
        $base = rtrim((string) (getenv('KIMIA_BASE_URL') ?: ''), '/');
        $user = (string) (getenv('KIMIA_USERNAME') ?: '');
        $pass = (string) (getenv('KIMIA_PASSWORD') ?: '');
        $swagger = (string) (getenv('KIMIA_SWAGGER_URL') ?: '');
        if ($swagger === '' && $base !== '') {
            $swagger = $base . '/swagger/v1/swagger.json';
        }

        $write = ((string) (getenv('KIMIA_WRITE_VERIFY_ENABLE') ?: '0')) === '1';
        $ownerAuth = trim((string) (getenv('KIMIA_WRITE_OWNER_AUTH') ?: ''));
        $expectedOwner = trim((string) (getenv('KIMIA_WRITE_OWNER_TOKEN') ?: '')); // required for any Write; no predictable default

        $allowRaw = (string) (getenv('KIMIA_WRITE_ACCOUNT_ALLOWLIST') ?: '');
        $allow = [];
        foreach (preg_split('/[\s,]+/', $allowRaw) ?: [] as $p) {
            if ($p !== '' && ctype_digit($p)) {
                $allow[] = (int) $p;
            }
        }
        $allow = array_values(array_unique($allow));

        // Explicit budget is required for every Write run. Zero is the default-deny baseline.
        $hardMax = [
            'buy' => 1,
            'sell' => 1,
            'receive' => 1,
            'pay' => 1,
            'create' => 5,
        ];
        $budget = [
            'buy' => 0,
            'sell' => 0,
            'receive' => 0,
            'pay' => 0,
            'create' => 0,
        ];
        $budgetOverride = (string) (getenv('KIMIA_WRITE_ATTEMPT_BUDGET') ?: '');
        if ($budgetOverride !== '') {
            foreach (explode(',', $budgetOverride) as $pair) {
                $kv = explode('=', trim($pair), 2);
                if (count($kv) === 2 && isset($budget[$kv[0]]) && ctype_digit($kv[1])) {
                    $budget[$kv[0]] = min($hardMax[$kv[0]], max(0, (int) $kv[1]));
                }
            }
        }

        // Verification evidence is intentionally fixed under gitignored var/kimia-verify/.
        $evidence = $repoRoot . '/var/kimia-verify';

        $timeout = (int) (getenv('KIMIA_TIMEOUT') ?: (getenv('KIMIA_HTTP_TIMEOUT') ?: '30'));
        if ($timeout < 5) {
            $timeout = 5;
        }
        if ($timeout > 120) {
            $timeout = 120;
        }

        return new self(
            baseUrl: $base,
            username: $user,
            password: $pass,
            swaggerUrl: $swagger,
            evidenceDir: $evidence,
            writeEnabled: $write,
            accountAllowlist: $allow,
            ownerAuthorization: $ownerAuth,
            expectedOwnerToken: $expectedOwner,
            attemptBudget: $budget,
            timeoutSeconds: $timeout,
        );
    }

    public function hasReadCredentials(): bool
    {
        return $this->baseUrl !== '' && $this->username !== '' && $this->password !== '';
    }

    /** Stable non-secret identifier for the current Owner-authorized batch. */
    public function ownerBatchFingerprint(): string
    {
        if ($this->expectedOwnerToken === '') {
            return '';
        }
        return hash('sha256', $this->expectedOwnerToken);
    }

    /** Redacted snapshot for evidence meta (no secrets). */
    public function publicMeta(): array
    {
        $host = parse_url($this->baseUrl, PHP_URL_HOST) ?: 'unknown';
        return [
            'base_host' => $host,
            'swagger_url_host' => parse_url($this->swaggerUrl, PHP_URL_HOST) ?: null,
            'write_enabled_flag' => $this->writeEnabled,
            'owner_auth_present' => $this->ownerAuthorization !== '',
            'owner_token_present' => $this->expectedOwnerToken !== '',
            'owner_batch_fingerprint' => $this->ownerBatchFingerprint() !== '' ? substr($this->ownerBatchFingerprint(), 0, 16) : null,
            'allowlist' => $this->accountAllowlist,
            'attempt_budget' => $this->attemptBudget,
            'timeout_seconds' => $this->timeoutSeconds,
        ];
    }
}
