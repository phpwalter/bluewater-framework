<?php

/**
 * @file RouteNotFound.php
 * @path src/Routing/RouteNotFound.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the routing exception raised when no discovered route matches a request.
 */

declare(strict_types=1);

namespace Bluewater\Routing;

use RuntimeException;

/**
 * Indicates that no discovered route matched an HTTP method and path.
 *
 * Application translates this technical routing failure to a 404 response and
 * suppresses its diagnostic message outside development.
 */
final class RouteNotFound extends RuntimeException
{
}
