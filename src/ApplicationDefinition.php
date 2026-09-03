<?php

/**
 * @file ApplicationDefinition.php
 * @path src/ApplicationDefinition.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the immutable paths, namespace, name, and environment that identify one hosted application.
 */

declare(strict_types=1);

namespace Bluewater;

/**
 * Carries the immutable identity and filesystem layout of one application.
 *
 * The definition performs no filesystem access and does not verify that its
 * paths exist. Host owns validation and runtime-directory provisioning.
 */
final readonly class ApplicationDefinition
{
    /**
     * Creates an application definition from canonical host paths.
     *
     * @param non-empty-string $name Application identifier accepted by Host.
     * @param non-empty-string $namespace Namespace without a required trailing separator.
     * @param non-empty-string $root Absolute application source root.
     * @param non-empty-string $cache Writable runtime cache directory.
     * @param non-empty-string $logs Writable runtime log directory.
     * @param non-empty-string $environment Runtime environment name.
     */
    public function __construct(
        public string $name,
        public string $namespace,
        public string $root,
        public string $cache,
        public string $logs,
        public string $environment = 'production',
    ) {
    }

    /** @return non-empty-string Application configuration directory. */
    public function configPath(): string
    {
        return $this->root . '/config';
    }

    /** @return non-empty-string Application endpoint directory. */
    public function endpointPath(): string
    {
        return $this->root . '/Endpoints';
    }
}
