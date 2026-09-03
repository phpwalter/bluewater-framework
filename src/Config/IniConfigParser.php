<?php

/**
 * @file IniConfigParser.php
 * @path src/Config/IniConfigParser.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Parses guarded INI configuration files and rejects unreadable, unguarded, or malformed sources.
 */

declare(strict_types=1);

namespace Bluewater\Config;

use RuntimeException;

/**
 * Parses INI data stored after an executable PHP exit guard.
 *
 * The parser reads the file as text and never includes or executes it. Only the
 * content after the first closing PHP tag is passed to PHP's typed INI parser.
 */
final class IniConfigParser
{
    /**
     * Parses one guarded file into a section-keyed configuration tree.
     *
     * @param non-empty-string $file Readable guarded INI file path.
     *
     * @return array<string, array<string, mixed>|mixed> Typed parsed values in source order.
     *
     * @throws RuntimeException When the file is unreadable, lacks a closing PHP
     *     guard, or contains malformed INI data.
     */
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
