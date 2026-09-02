<?php

/**
 * @file UserDto.php
 * @path examples/host/app/app_1/DTO/UserDto.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the immutable user dto data-transfer contract used by the example application.
 */

declare(strict_types=1);

namespace Apps\App1\DTO;

/**
 * Carries the public serialized representation of an example user.
 *
 * The DTO is immutable and deliberately excludes credentials and persistence
 * metadata. Equality is value-based across all three public properties.
 */
final readonly class UserDto
{
    /**
     * @param positive-int $id Persistent user identifier.
     * @param non-empty-string $email Public example email address.
     * @param non-empty-string $name Public display name.
     */
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
    ) {
    }
}
