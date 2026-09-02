<?php

/**
 * @file RuntimeAdapter.php
 * @path src/Runtime/RuntimeAdapter.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the runtime boundary for obtaining a request and emitting its response.
 */

declare(strict_types=1);

namespace Bluewater\Runtime;

use Bluewater\Http\Request;
use Bluewater\Http\Response;

/**
 * Defines transport-specific request acquisition and response emission.
 *
 * Implementations own all process-global I/O. Application invokes request()
 * once and emit() once per run and does not retain transport resources.
 */
interface RuntimeAdapter
{
    /** Returns one immutable request created from the current runtime input. */
    public function request(): Request;

    /** Emits status, headers, and body to the runtime exactly once. */
    public function emit(Response $response): void;
}
