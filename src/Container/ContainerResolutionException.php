<?php

/**
 * @file ContainerResolutionException.php
 * @path src/Container/ContainerResolutionException.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the PSR-11 container exception raised when a known service cannot be constructed.
 */

declare(strict_types=1);

namespace Bluewater\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/** Indicates that a known service cannot be constructed by the container. */
final class ContainerResolutionException extends RuntimeException implements ContainerExceptionInterface
{
}
