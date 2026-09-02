<?php

/**
 * @file _middleware.php
 * @path examples/host/app/app_1/Endpoints/admin/_middleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Declares the ordered middleware applied to every example administrative endpoint.
 */

declare(strict_types=1);

use Bluewater\Auth\ApiKeyMiddleware;

return [ApiKeyMiddleware::class];
