<?php

/**
 * @file Middleware.php
 * @path src/Middleware/Middleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the callable-based request middleware contract used by the Bluewater pipeline.
 */

declare(strict_types=1);

namespace Bluewater\Middleware;

use Bluewater\Http\Request;
use Bluewater\Http\Response;

/**
 * Defines synchronous middleware around a Bluewater request handler.
 *
 * Implementations decide whether and how often to invoke the downstream
 * callable. They must return a Response and document any additional side effects.
 */
interface Middleware
{
    /**
     * Processes a request around the downstream handler.
     *
     * @param callable(Request): Response $next Downstream pipeline continuation.
     */
    public function process(Request $request, callable $next): Response;
}
