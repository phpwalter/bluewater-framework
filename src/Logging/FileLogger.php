<?php

/**
 * @file FileLogger.php
 * @path src/Logging/FileLogger.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Implements append-only PSR-3 file logging with placeholder interpolation and structured context encoding.
 */

declare(strict_types=1);

namespace Bluewater\Logging;

use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * Appends one structured line per PSR-3 event to a configured file.
 *
 * Context keys associated with credentials are recursively replaced with a
 * redaction marker before placeholder interpolation and JSON serialization.
 * Each append requests an exclusive lock. The logger does not rotate files,
 * create directories, persist audit records, or guarantee cross-host ordering.
 */
final class FileLogger extends AbstractLogger
{
    /** Keys whose values must never be written to log output. */
    private const string SENSITIVE_KEY_PATTERN =
        '/authorization|cookie|password|passwd|secret|token|api[-_]?key|credential/i';

    /**
     * Creates a logger without opening or creating the target file.
     *
     * @param non-empty-string $file Append-only log file path.
     */
    public function __construct(private readonly string $file)
    {
    }

    /**
     * Redacts sensitive context and atomically appends a timestamped log line.
     *
     * @param mixed $level PSR-3 level converted to an uppercase string.
     * @param array<string, mixed> $context Structured values; sensitive keys
     *     are recursively replaced before interpolation or encoding.
     *
     * @throws RuntimeException When the line cannot be appended completely.
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $context = $this->redact($context);
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('c'),
            strtoupper((string) $level),
            $this->interpolate((string) $message, $context),
            $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
        );
        $written = file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
        if ($written !== strlen($line)) {
            throw new RuntimeException('Unable to append the complete log entry.');
        }
    }

    /**
     * Replaces scalar or stringable placeholders from already-redacted context.
     *
     * @param array<string, mixed> $context Redacted context values.
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (is_null($value) || is_scalar($value) || $value instanceof Stringable) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }
        return strtr($message, $replace);
    }

    /**
     * Returns a recursively redacted copy of context without mutating the input.
     *
     * @param array<string, mixed> $context Untrusted logging context.
     *
     * @return array<string, mixed> Context safe for this logger's output policy.
     */
    private function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (preg_match(self::SENSITIVE_KEY_PATTERN, (string) $key) === 1) {
                $context[$key] = '[REDACTED]';
                continue;
            }
            if (is_array($value)) {
                $context[$key] = $this->redact($value);
            }
        }

        return $context;
    }
}
