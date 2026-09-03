<?php

/**
 * @file FileLoggerTest.php
 * @path tests/Logging/FileLoggerTest.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Verifies recursive credential redaction in file log output.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Logging;

use Bluewater\Logging\FileLogger;
use PHPUnit\Framework\TestCase;

/** Verifies that sensitive context never reaches interpolated or structured log text. */
final class FileLoggerTest extends TestCase
{
    /** Confirms direct and nested credential values are replaced before output. */
    public function testSensitiveContextIsRedacted(): void
    {
        $file = sys_get_temp_dir() . '/bluewater-log-' . bin2hex(random_bytes(6)) . '.log';

        try {
            $logger = new FileLogger($file);
            $logger->info('Token {token}', [
                'token' => 'unit-test-token-value',
                'nested' => ['password' => 'unit-test-password-value'],
                'safe' => 'visible',
            ]);
            $contents = file_get_contents($file);

            self::assertIsString($contents);
            self::assertStringNotContainsString('unit-test-token-value', $contents);
            self::assertStringNotContainsString('unit-test-password-value', $contents);
            self::assertStringContainsString('[REDACTED]', $contents);
            self::assertStringContainsString('visible', $contents);
        } finally {
            @unlink($file);
        }
    }
}
