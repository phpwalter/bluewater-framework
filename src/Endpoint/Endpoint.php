<?php

/**
 * @file Endpoint.php
 * @path src/Endpoint/Endpoint.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the marker base class required for discoverable HTTP endpoint handlers.
 */

declare(strict_types=1);

namespace Bluewater\Endpoint;

/**
 * Marks a class as an HTTP endpoint eligible for reflective dispatch.
 *
 * Router discovers public instance methods declared by concrete subclasses;
 * Endpoint itself introduces no state, behavior, authorization, or serialization.
 */
abstract class Endpoint
{
}
