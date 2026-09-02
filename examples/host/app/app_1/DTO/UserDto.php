<?php

declare(strict_types=1);

namespace Apps\App1\DTO;

final readonly class UserDto
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
    ) {}
}
