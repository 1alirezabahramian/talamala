<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia\Verify;

/**
 * Writes evidence under var/kimia-verify/ (gitignored). Never logs secrets.
 * State/evidence write failures throw so mutation code remains fail-closed.
 */
final class KimiaVerifyEvidence
{
    public function __construct(private readonly string $dir)
    {
        if (!is_dir($this->dir) && !mkdir($this->dir, 0700, true) && !is_dir($this->dir)) {
            throw new \RuntimeException('Cannot create evidence dir');
        }
    }

    public function path(string $relative): string
    {
        $relative = ltrim($relative, '/');
        if ($relative === '' || str_contains($relative, "\0")) {
            throw new \InvalidArgumentException('Invalid evidence relative path');
        }
        $parts = preg_split('~[\\\\/]+~', $relative) ?: [];
        if (in_array('..', $parts, true)) {
            throw new \InvalidArgumentException('Evidence path traversal rejected');
        }
        return $this->dir . '/' . $relative;
    }

    public function writeJson(string $relative, mixed $data): void
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!is_string($encoded)) {
            throw new \RuntimeException('Cannot encode evidence JSON');
        }
        $this->writeRaw($relative, $encoded . "\n");
    }

    public function writeRaw(string $relative, string $body): void
    {
        $path = $this->path($relative);
        $parent = dirname($path);
        if (!is_dir($parent) && !mkdir($parent, 0700, true) && !is_dir($parent)) {
            throw new \RuntimeException('Cannot create evidence parent dir');
        }
        if (file_put_contents($path, $body, LOCK_EX) === false) {
            throw new \RuntimeException('Cannot write evidence file: ' . $relative);
        }
        @chmod($path, 0600);
    }

    public function appendLog(string $line): void
    {
        $line = preg_replace('/(password|passwd|token|authorization|secret)\s*[:=]\s*\S+/i', '$1=***', $line) ?? $line;
        $path = $this->path('runner.log');
        if (file_put_contents($path, gmdate('c') . ' ' . $line . "\n", FILE_APPEND | LOCK_EX) === false) {
            throw new \RuntimeException('Cannot append verification log');
        }
        @chmod($path, 0600);
    }

    /** @return array<string, mixed> */
    public function loadJson(string $relative, array $default = []): array
    {
        $path = $this->path($relative);
        if (!is_file($path)) {
            return $default;
        }
        $raw = file_get_contents($path);
        if (!is_string($raw)) {
            throw new \RuntimeException('Cannot read evidence state: ' . $relative);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Corrupt evidence JSON state: ' . $relative);
        }
        return $decoded;
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function withExclusiveLock(string $relative, callable $callback): mixed
    {
        $path = $this->path($relative);
        $parent = dirname($path);
        if (!is_dir($parent) && !mkdir($parent, 0700, true) && !is_dir($parent)) {
            throw new \RuntimeException('Cannot create lock parent dir');
        }
        $fh = fopen($path, 'c+');
        if ($fh === false) {
            throw new \RuntimeException('Cannot open verification lock');
        }
        try {
            if (!flock($fh, LOCK_EX)) {
                throw new \RuntimeException('Cannot acquire verification lock');
            }
            return $callback();
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}
