<?php

declare(strict_types=1);

namespace Bluewater\Tests\Config;

use Bluewater\Config\ConfigFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigFactoryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/bluewater-config-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/core', 0777, true);
        mkdir($this->root . '/app', 0777, true);
        mkdir($this->root . '/cache', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testAppOverridesCoreAndReferencesResolve(): void
    {
        file_put_contents($this->root . '/core/Bluewater.ini.php', "<?php\nexit;\n?>\n[constants]\nBW_VER=\"8.0ai\"\nBW_ENV=\"production\"\n[paths]\nLOG=\"{APP_ROOT}/logs\"\n");
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

    public function testLockedBluewaterVersionCannotBeOverridden(): void
    {
        file_put_contents($this->root . '/core/Bluewater.ini.php', "<?php\nexit;\n?>\n[constants]\nBW_VER=\"8.0ai\"\n");
        file_put_contents($this->root . '/app/App.ini.php', "<?php\nexit;\n?>\n[constants]\nBW_VER=\"9.0\"\n");

        $this->expectException(RuntimeException::class);
        (new ConfigFactory($this->root . '/core', $this->root . '/app', $this->root . '/cache'))->create();
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) { @unlink($path); return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $this->remove($path . '/' . $item);
        }
        @rmdir($path);
    }
}
