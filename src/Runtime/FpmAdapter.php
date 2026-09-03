<?php

/**
 * @file FpmAdapter.php
 * @path src/Runtime/FpmAdapter.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Adapts PHP-FPM globals and output functions to the framework runtime boundary.
 */

declare(strict_types=1);

namespace Bluewater\Runtime;

use Bluewater\Http\Request;
use Bluewater\Http\Response;

/**
 * Bridges PHP-FPM process globals and output functions to Bluewater HTTP values.
 *
 * The adapter is stateless and reusable, but each request() call re-reads current
 * globals and input while emit() mutates process response state and writes output.
 */
final class FpmAdapter implements RuntimeAdapter
{
    /** Reads the current PHP-FPM request globals and input stream once. */
    public function request(): Request
    {
        return Request::fromGlobals();
    }

    /**
     * Emits HTTP status, replaces equal response headers, and writes body bytes.
     *
     * Partial output is possible if a later header or body write fails.
     */
    public function emit(Response $response): void
    {
        http_response_code($response->status);
        foreach ($response->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }
        echo $response->body;
    }
}
