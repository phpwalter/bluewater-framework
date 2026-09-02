<?php

declare(strict_types=1);

namespace Bluewater\Auth;

interface OAuthIntrospector
{
    /** @return array<string, mixed>|null */
    public function introspect(string $token): ?array;
}
