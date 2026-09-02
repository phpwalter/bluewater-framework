<?php

declare(strict_types=1);

namespace Bluewater\Config;

use RuntimeException;

final class IniConfigParser
{
    public function parse(string $file): array
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException("Unable to read configuration file: {$file}");
        }

        $endGuard = strpos($contents, '?>');
        if ($endGuard === false) {
            throw new RuntimeException("Protected configuration file is missing its PHP guard: {$file}");
        }

        $ini = substr($contents, $endGuard + 2);
        $parsed = parse_ini_string($ini, true, INI_SCANNER_TYPED);
        if ($parsed === false) {
            throw new RuntimeException("Unable to parse configuration file: {$file}");
        }

        return $parsed;
    }
}
