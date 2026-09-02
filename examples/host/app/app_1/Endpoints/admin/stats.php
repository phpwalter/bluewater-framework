<?php

declare(strict_types=1);

namespace Apps\App1\Endpoints\admin;

use Apps\App1\Services\UserRepository;
use Bluewater\Endpoint\Endpoint;
use Bluewater\OpenApi\Summary;

final class Stats extends Endpoint
{
    #[Summary('Admin statistics')]
    public function get(UserRepository $users): array
    {
        return ['users' => count($users->all())];
    }
}
