<?php

/**
 * @file stats.php
 * @path examples/host/app/app_1/Endpoints/admin/stats.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the example stats HTTP endpoint and its serialized response contract.
 */

declare(strict_types=1);

namespace Apps\App1\Endpoints\admin;

use Apps\App1\Services\UserRepository;
use Bluewater\Endpoint\Endpoint;
use Bluewater\OpenApi\Summary;

/**
 * Reports administrative example statistics after directory-level API-key authentication.
 *
 * Authentication is enforced by the parent `_middleware.php`; this endpoint
 * does not perform an additional authorization decision.
 */
final class Stats extends Endpoint
{
    /** @return array{users: non-negative-int} Current total user count. */
    #[Summary('Admin statistics')]
    public function get(UserRepository $users): array
    {
        return ['users' => count($users->all())];
    }
}
