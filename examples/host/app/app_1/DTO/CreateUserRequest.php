<?php

declare(strict_types=1);

namespace Apps\App1\DTO;

use Bluewater\Validation\Email;
use Bluewater\Validation\MinLength;
use Bluewater\Validation\Required;

final readonly class CreateUserRequest
{
    public function __construct(
        #[Required, Email]
        public string $email,
        #[Required, MinLength(2)]
        public string $name,
    ) {}
}
