<?php

/**
 * @file CreateUserRequest.php
 * @path examples/host/app/app_1/DTO/CreateUserRequest.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the immutable create user request data-transfer contract used by the example application.
 */

declare(strict_types=1);

namespace Apps\App1\DTO;

use Bluewater\Validation\Email;
use Bluewater\Validation\MinLength;
use Bluewater\Validation\Required;

/**
 * Carries the validated input required to create an example user.
 *
 * Email must pass FILTER_VALIDATE_EMAIL and name must contain at least two
 * Unicode characters. Values are immutable and are not normalized or redacted;
 * callers must not log the DTO when application data is sensitive.
 */
final readonly class CreateUserRequest
{
    /**
     * Creates an immutable request; attribute validation occurs during dispatch.
     *
     * @param non-empty-string $email User email address.
     * @param non-empty-string $name Display name of at least two characters.
     */
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required, MinLength(2)]
        public string $name,
    ) {
    }
}
