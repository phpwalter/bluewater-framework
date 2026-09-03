<?php

/**
 * @file ContainerNotFound.php
 * @path src/Container/ContainerNotFound.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the PSR-11 not-found exception raised when a requested service has no resolution target.
 */

declare(strict_types=1);

namespace Bluewater\Container;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/** Indicates that a requested service identifier has no resolution target. */
final class ContainerNotFound extends RuntimeException implements NotFoundExceptionInterface
{
}
