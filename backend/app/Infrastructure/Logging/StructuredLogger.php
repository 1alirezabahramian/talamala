<?php

declare(strict_types=1);

namespace Talamala\Infrastructure\Logging;

/**
 * JSON line logger — no secrets, correlation-friendly.
 */
final class StructuredLogger
{
    /** @var list<array<string, mixed>> */
    public array $records = [];

    public function __construct(
        private readonly string $channel = 'talamala',
        private readonly ?string $streamPath = null,
    ) {}

    public static function fromEnv(string $channel = 'talamala'): self
    {
        $path = getenv('TALAMALA_LOG_PATH') ?: null;
        if (is_string($path) && $path !== '') {
            $dir = dirname($path);
            if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            return new self($channel, $path);
        }
        return new self($channel, null);
    }


    /**
     * @param array<string, mixed> $context
     */
    public function info(string $event, array $context = []): void
    {
        $this->write('info', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $event, array $context = []): void
    {
        $this->write('warning', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $event, array $context = []): void
    {
        $this->write('error', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function write(string $level, string $event, array $context): void
    {
        $context = $this->redact($context);
        $row = [
            'ts' => gmdate('c'),
            'level' => $level,
            'channel' => $this->channel,
            'event' => $event,
            'context' => $context,
        ];
        $this->records[] = $row;
        $line = json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        if ($this->streamPath) {
            @file_put_contents($this->streamPath, $line, FILE_APPEND);
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function redact(array $context): array
    {
        $sensitive = ['password', 'code', 'otp', 'token', 'authorization', 'secret', 'api_key', 'access_token', 'national_code', 'national_id', 'bearer'];
        foreach ($context as $k => $v) {
            $lk = strtolower((string) $k);
            foreach ($sensitive as $s) {
                if (str_contains($lk, $s)) {
                    $context[$k] = '[redacted]';
                    break;
                }
            }
        }
        return $context;
    }
}
