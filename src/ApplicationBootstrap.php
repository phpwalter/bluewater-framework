<?php

declare(strict_types=1);

namespace Bluewater;

interface ApplicationBootstrap
{
    public function register(Application $app): void;
    public function boot(Application $app): void;
}
