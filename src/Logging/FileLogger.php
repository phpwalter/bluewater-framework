<?php

declare(strict_types=1);

namespace Bluewater\Logging;

use Psr\Log\AbstractLogger;
use Stringable;

final class FileLogger extends AbstractLogger
{
    public function __construct(private readonly string $file) {}

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('c'),
            strtoupper((string) $level),
            $this->interpolate((string) $message, $context),
            $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
        );
        file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }

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
}
