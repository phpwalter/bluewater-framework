<?php

/**
 * @file ConfigFactoryTest.php
 * @path tests/Config/ConfigFactoryTest.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Verifies the config factory test behavior and its observable framework contracts.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Config;

use Bluewater\Config\ConfigFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** Verifies configuration precedence, reference resolution, caching, and locked keys. */
final class ConfigFactoryTest extends TestCase
{
    /** @var non-empty-string Per-test temporary configuration root. */
    private string $root;

    /** Creates isolated core, application, and cache directories. */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/bluewater-config-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/core', 0777, true);
        mkdir($this->root . '/app', 0777, true);
        mkdir($this->root . '/cache', 0777, true);
    }

    /** Removes every temporary file and directory created by the test. */
    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    /** Verifies typed application overrides, references, and cache creation. */
    public function testAppOverridesCoreAndReferencesResolve(): void
    {
        file_put_contents(
            $this->root . '/core/Bluewater.ini.php',
            "<?php\nexit;\n?>\n[constants]\nBW_VER=\"8.0ai\"\nBW_ENV=\"production\""
            . "\n[paths]\nLOG=\"{APP_ROOT}/logs\"\n",
        );
        file_put_contents($this->root . '/app/App.ini.php', "<?php\nexit;\n?>\n[constants]\nBW_ENV=\"development\"\n");

        $config = (new ConfigFactory(
            $this->root . '/core',
            $this->root . '/app',
            $this->root . '/cache',
            ['APP_ROOT' => '/srv/app'],
        ))->create();

        self::assertSame('8.0ai', $config->get('BW_VER'));
        self::assertSame('development', $config->get('BW_ENV'));
        self::assertSame('/srv/app/logs', $config->get('paths.LOG'));
        self::assertFileExists($this->root . '/cache/config.php');
    }

    /** Verifies that application configuration cannot change BW_VER. */
    public function testLockedBluewaterVersionCannotBeOverridden(): void
    {
        file_put_contents($this->root . '/core/Bluewater.ini.php', "<?php\nexit;\n?>\n[constants]\nBW_VER=\"8.0ai\"\n");
        file_put_contents($this->root . '/app/App.ini.php', "<?php\nexit;\n?>\n[constants]\nBW_VER=\"9.0\"\n");

        $this->expectException(RuntimeException::class);
        (new ConfigFactory($this->root . '/core', $this->root . '/app', $this->root . '/cache'))->create();
    }

    /** Recursively removes one test-owned path. */
    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            @unlink($path);
            return;
        }

        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $this->remove($path . '/' . $item);
        }
        @rmdir($path);
    }
}
