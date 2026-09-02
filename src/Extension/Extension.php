<?php

declare(strict_types=1);

namespace Bluewater\Extension;

use Bluewater\Application;

interface Extension
{
    public function register(Application $app): void;
    public function boot(Application $app): void;
}
